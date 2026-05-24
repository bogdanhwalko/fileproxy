<?php

namespace App\Services;

use App\Models\ManagedFile;
use App\Models\TelegramBotToken;
use App\Models\TelegramStorageGroup;
use Illuminate\Http\Client\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

class TelegramFileStorageService
{
    private const TELEGRAM_API_ATTEMPTS = 5;

    private const TELEGRAM_MAX_RETRY_SECONDS = 60;

    public function sendDocument(UploadedFile $file, TelegramStorageGroup $group): array
    {
        return $this->sendDocumentFromPath(
            $file->getRealPath(),
            $file->getClientOriginalName(),
            $group,
            $file->getSize() ?: 0,
            $file->getMimeType() ?: 'application/octet-stream'
        );
    }

    public function sendDocumentFromPath(
        string $absolutePath,
        string $originalName,
        TelegramStorageGroup $group,
        int $fallbackSize = 0,
        string $fallbackMimeType = 'application/octet-stream'
    ): array {
        $bot = $group->botToken;

        if (! $bot) {
            throw new RuntimeException('Для Telegram-групи не знайдено бота.');
        }

        if (! is_file($absolutePath)) {
            throw new RuntimeException('Файл для відправки в Telegram не знайдено на сервері.');
        }

        $response = $this->sendDocumentRequest($absolutePath, $originalName, $group, $bot);

        if (! $response->successful() || ! $response->json('ok')) {
            $description = trim((string) $response->json('description'));
            $code = $response->status();
            $retryAfter = data_get($response->json(), 'parameters.retry_after');

            $reason = $description !== ''
                ? "Telegram API {$code}: {$description}".($retryAfter ? " (retry_after={$retryAfter}s)" : '')
                : "Telegram API {$code}: відповідь без опису";

            throw new RuntimeException($reason);
        }

        $payload = $response->json();
        $media = $this->extractMediaFromPayload($payload);

        if (! data_get($media, 'file_id')) {
            throw new RuntimeException('Telegram не повернув file_id для завантаженого файла.');
        }

        $resolvedSize = (int) data_get($media, 'file_size');
        if ($resolvedSize === 0) {
            $resolvedSize = $fallbackSize > 0 ? $fallbackSize : (int) (@filesize($absolutePath) ?: 0);
        }

        return [
            'chat_id' => (string) data_get($payload, 'result.chat.id', $group->chat_id),
            'message_id' => (int) data_get($payload, 'result.message_id'),
            'file_id' => (string) data_get($media, 'file_id'),
            'file_unique_id' => data_get($media, 'file_unique_id'),
            'file_size' => $resolvedSize,
            'mime_type' => data_get($media, 'mime_type') ?: $fallbackMimeType,
        ];
    }

    public function downloadToTemporaryPath(ManagedFile $file): string
    {
        $bot = $file->telegramBotToken;

        if (! $bot || ! $file->telegram_file_id) {
            throw new RuntimeException('Для файла немає Telegram-метаданих для завантаження.');
        }

        $filePath = $this->getTelegramFilePath($bot, $file->telegram_file_id);

        $directory = 'telegram-temp/'.$file->user_id;
        Storage::disk('local')->makeDirectory($directory);

        $filename = Str::uuid().'_'.$this->safeFilename($file->stored_name ?: basename($filePath));
        $storagePath = $directory.'/'.$filename;
        $absolutePath = Storage::disk('local')->path($storagePath);

        $response = $this->telegramGet($this->fileUrl($bot->token, $filePath), ['sink' => $absolutePath]);

        if (! $response->successful()) {
            @unlink($absolutePath);

            throw new RuntimeException('Не вдалося тимчасово завантажити файл із Telegram.');
        }

        return $absolutePath;
    }

    public function downloadStream(ManagedFile $file): StreamInterface
    {
        $bot = $file->telegramBotToken;

        if (! $bot || ! $file->telegram_file_id) {
            throw new RuntimeException('Telegram file metadata is missing for download.');
        }

        $filePath = $this->getTelegramFilePath($bot, $file->telegram_file_id);
        $response = $this->telegramGet($this->fileUrl($bot->token, $filePath), ['stream' => true]);

        if (! $response->successful()) {
            throw new RuntimeException('Could not stream file from Telegram.');
        }

        return $response->toPsrResponse()->getBody();
    }

    public function deleteMessage(ManagedFile $file): bool
    {
        $bot = $file->telegramBotToken;

        if (! $bot || ! $file->telegram_chat_id || ! $file->telegram_message_id) {
            return false;
        }

        return $this->deleteMessageRaw($bot, (string) $file->telegram_chat_id, (int) $file->telegram_message_id);
    }

    public function deleteMessageRaw(TelegramBotToken $bot, string $chatId, int $messageId): bool
    {
        $response = $this->telegramPostJson($bot, 'deleteMessage', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
        ]);

        return $response->successful() && (bool) $response->json('ok', false);
    }

    /**
     * Synchronously fetch a file's bytes from Telegram by file_id.
     * Used by ProtectedFileService to load each encrypted chunk into memory for AEAD decryption.
     */
    public function downloadFileBytes(TelegramBotToken $bot, string $fileId): string
    {
        $filePath = $this->getTelegramFilePath($bot, $fileId);
        $response = $this->telegramGet($this->fileUrl($bot->token, $filePath));

        if (! $response->successful()) {
            throw new RuntimeException('Could not fetch chunk bytes from Telegram (file_id='.$fileId.').');
        }

        return (string) $response->body();
    }

    private function getTelegramFilePath(TelegramBotToken $bot, string $fileId): string
    {
        $response = $this->telegramPostJson($bot, 'getFile', [
            'file_id' => $fileId,
        ]);

        if (! $response->successful() || ! $response->json('ok')) {
            throw new RuntimeException('Telegram не повернув шлях файла.');
        }

        $filePath = $response->json('result.file_path');

        if (! $filePath) {
            throw new RuntimeException('Telegram повернув порожній шлях файла.');
        }

        return (string) $filePath;
    }

    private function sendDocumentRequest(string $absolutePath, string $originalName, TelegramStorageGroup $group, TelegramBotToken $bot): Response
    {
        $response = null;

        for ($attempt = 1; $attempt <= self::TELEGRAM_API_ATTEMPTS; $attempt++) {
            $stream = fopen($absolutePath, 'r');

            if ($stream === false) {
                throw new RuntimeException('Не вдалося прочитати файл перед відправкою в Telegram.');
            }

            try {
                $response = Http::timeout(120)
                    ->attach('document', $stream, $originalName)
                    ->post($this->apiUrl($bot->token, 'sendDocument'), [
                        'chat_id' => $group->chat_id,
                        'caption' => $originalName,
                        'disable_content_type_detection' => true,
                    ]);
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }

            if (! $this->shouldRetryTelegramRequest($response, $attempt)) {
                return $response;
            }

            $this->pauseBeforeTelegramRetry($response);
        }

        return $response;
    }

    private function telegramPostJson(TelegramBotToken $bot, string $method, array $payload): Response
    {
        return $this->withTelegramRetries(fn (): Response => Http::asJson()
            ->post($this->apiUrl($bot->token, $method), $payload));
    }

    private function telegramGet(string $url, array $options = []): Response
    {
        return $this->withTelegramRetries(fn (): Response => Http::timeout(120)
            ->withOptions($options)
            ->get($url));
    }

    private function withTelegramRetries(callable $request): Response
    {
        $response = null;

        for ($attempt = 1; $attempt <= self::TELEGRAM_API_ATTEMPTS; $attempt++) {
            $response = $request();

            if (! $this->shouldRetryTelegramRequest($response, $attempt)) {
                return $response;
            }

            $this->pauseBeforeTelegramRetry($response);
        }

        return $response;
    }

    private function shouldRetryTelegramRequest(Response $response, int $attempt): bool
    {
        if ($attempt >= self::TELEGRAM_API_ATTEMPTS) {
            return false;
        }

        // Standard rate-limit / server error
        if ($response->status() === 429 || $response->serverError()) {
            return true;
        }

        // Flood control can also surface as 200 OK with {ok: false, parameters.retry_after}.
        // Critical: only parse JSON for JSON responses — calling ->json() on a binary
        // stream response (file download) would consume the body and leave the caller
        // with an empty stream.
        if ($this->isJsonResponse($response)) {
            $body = $response->json();
            if (is_array($body) && ($body['ok'] ?? true) === false && data_get($body, 'parameters.retry_after')) {
                return true;
            }
        }

        return false;
    }

    private function pauseBeforeTelegramRetry(Response $response): void
    {
        $seconds = 0;

        if ($this->isJsonResponse($response)) {
            $body = $response->json();
            $seconds = (int) (data_get($body, 'parameters.retry_after') ?? 0);
        }

        $seconds = max(0, min(self::TELEGRAM_MAX_RETRY_SECONDS, $seconds));

        if ($seconds > 0) {
            // Add small jitter (0–750ms) so multiple concurrent uploads don't all unblock at the same instant
            sleep($seconds);
            usleep(random_int(0, 750_000));

            return;
        }

        // No retry_after — back off with mild jitter
        usleep(random_int(250_000, 750_000));
    }

    private function isJsonResponse(Response $response): bool
    {
        $contentType = strtolower((string) $response->header('Content-Type'));

        return str_contains($contentType, 'json');
    }

    private function apiUrl(string $token, string $method): string
    {
        return 'https://api.telegram.org/bot'.$token.'/'.$method;
    }

    private function fileUrl(string $token, string $filePath): string
    {
        return 'https://api.telegram.org/file/bot'.$token.'/'.$filePath;
    }

    private function safeFilename(string $filename): string
    {
        return preg_replace('/[^A-Za-z0-9._-]+/', '_', $filename) ?: 'telegram-file';
    }

    private function extractMediaFromPayload(?array $payload): array
    {
        foreach (['document', 'video', 'animation', 'audio', 'voice', 'video_note'] as $type) {
            $media = data_get($payload, "result.{$type}");

            if (is_array($media) && ! empty($media['file_id'])) {
                return $media;
            }
        }

        $photos = data_get($payload, 'result.photo');

        if (is_array($photos) && $photos !== []) {
            $largest = end($photos);

            if (is_array($largest) && ! empty($largest['file_id'])) {
                return $largest;
            }
        }

        return [];
    }
}
