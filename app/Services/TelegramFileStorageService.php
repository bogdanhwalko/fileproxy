<?php

namespace App\Services;

use App\Models\ManagedFile;
use App\Models\TelegramBotToken;
use App\Models\TelegramStorageGroup;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

class TelegramFileStorageService
{
    public function sendDocument(UploadedFile $file, TelegramStorageGroup $group): array
    {
        $bot = $group->botToken;

        if (! $bot) {
            throw new RuntimeException('Для Telegram-групи не знайдено бота.');
        }

        $stream = fopen($file->getRealPath(), 'r');

        if ($stream === false) {
            throw new RuntimeException('Не вдалося прочитати файл перед відправкою в Telegram.');
        }

        try {
            $response = Http::timeout(120)
                ->attach('document', $stream, $file->getClientOriginalName())
                ->post($this->apiUrl($bot->token, 'sendDocument'), [
                    'chat_id' => $group->chat_id,
                    'caption' => $file->getClientOriginalName(),
                ]);
        } finally {
            fclose($stream);
        }

        if (! $response->successful() || ! $response->json('ok')) {
            throw new RuntimeException('Telegram не прийняв файл. Перевірте токен бота, group chat_id і права бота в групі.');
        }

        $payload = $response->json();
        $document = data_get($payload, 'result.document', []);
        $fileId = data_get($document, 'file_id');

        if (! $fileId) {
            throw new RuntimeException('Telegram не повернув file_id для завантаженого файла.');
        }

        return [
            'chat_id' => (string) data_get($payload, 'result.chat.id', $group->chat_id),
            'message_id' => (int) data_get($payload, 'result.message_id'),
            'file_id' => (string) $fileId,
            'file_unique_id' => data_get($document, 'file_unique_id'),
            'file_size' => (int) (data_get($document, 'file_size') ?: ($file->getSize() ?: 0)),
            'mime_type' => data_get($document, 'mime_type') ?: ($file->getMimeType() ?: 'application/octet-stream'),
            'payload' => $payload,
        ];
    }

    public function downloadToTemporaryPath(ManagedFile $file): string
    {
        $bot = $file->telegramBotToken;

        if (! $bot || ! $file->telegram_file_id) {
            throw new RuntimeException('Для файла немає Telegram-метаданих для завантаження.');
        }

        $filePath = $this->getTelegramFilePath($bot, $file->telegram_file_id);
        $response = Http::timeout(120)->get($this->fileUrl($bot->token, $filePath));

        if (! $response->successful()) {
            throw new RuntimeException('Не вдалося тимчасово завантажити файл із Telegram.');
        }

        $directory = 'telegram-temp/'.$file->user_id;
        $filename = Str::uuid().'_'.$this->safeFilename($file->stored_name ?: basename($filePath));
        $storagePath = $directory.'/'.$filename;

        Storage::disk('local')->put($storagePath, $response->body());

        return Storage::disk('local')->path($storagePath);
    }

    public function downloadStream(ManagedFile $file): StreamInterface
    {
        $bot = $file->telegramBotToken;

        if (! $bot || ! $file->telegram_file_id) {
            throw new RuntimeException('Telegram file metadata is missing for download.');
        }

        $filePath = $this->getTelegramFilePath($bot, $file->telegram_file_id);
        $response = Http::timeout(120)
            ->withOptions(['stream' => true])
            ->get($this->fileUrl($bot->token, $filePath));

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

        $response = Http::asJson()->post($this->apiUrl($bot->token, 'deleteMessage'), [
            'chat_id' => $file->telegram_chat_id,
            'message_id' => $file->telegram_message_id,
        ]);

        return $response->successful() && (bool) $response->json('ok', false);
    }

    private function getTelegramFilePath(TelegramBotToken $bot, string $fileId): string
    {
        $response = Http::asJson()->post($this->apiUrl($bot->token, 'getFile'), [
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
}
