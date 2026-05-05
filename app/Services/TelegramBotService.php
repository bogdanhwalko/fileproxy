<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Throwable;

class TelegramBotService
{
    private const REQUEST_TIMEOUT = 10;

    public function configured(): bool
    {
        return trim((string) config('services.telegram.bot_token')) !== '';
    }

    public function sendMessage(int|string $chatId, string $text, ?array $replyMarkup = null): bool
    {
        if (! $this->configured()) {
            return false;
        }

        $payload = [
            'chat_id' => $chatId,
            'text' => $text,
        ];

        if ($replyMarkup !== null) {
            $payload['reply_markup'] = $replyMarkup;
        }

        try {
            $response = Http::asJson()
                ->timeout(self::REQUEST_TIMEOUT)
                ->post($this->apiUrl('sendMessage'), $payload);

            return $response->successful();
        } catch (Throwable) {
            return false;
        }
    }

    public function getUpdates(?int $offset = null, int $timeout = 25): array
    {
        if (! $this->configured()) {
            return [];
        }

        try {
            $response = Http::asJson()
                ->timeout($timeout + 5)
                ->get($this->apiUrl('getUpdates'), array_filter([
                    'offset' => $offset,
                    'timeout' => $timeout,
                ], fn ($value) => $value !== null));
        } catch (Throwable) {
            return [];
        }

        if (! $response->successful()) {
            return [];
        }

        return $response->json('result', []);
    }

    public function setWebhook(string $url, bool $dropPendingUpdates = false): array
    {
        if (! $this->configured()) {
            return ['ok' => false, 'description' => 'TELEGRAM_BOT_TOKEN is not configured.'];
        }

        try {
            $response = Http::asJson()
                ->timeout(self::REQUEST_TIMEOUT)
                ->post($this->apiUrl('setWebhook'), [
                    'url' => $url,
                    'allowed_updates' => ['message'],
                    'drop_pending_updates' => $dropPendingUpdates,
                ]);
        } catch (Throwable $exception) {
            return ['ok' => false, 'description' => $exception->getMessage()];
        }

        return $response->json() ?: [
            'ok' => $response->successful(),
            'description' => $response->body(),
        ];
    }

    public function getWebhookInfo(): array
    {
        if (! $this->configured()) {
            return ['ok' => false, 'description' => 'TELEGRAM_BOT_TOKEN is not configured.'];
        }

        try {
            $response = Http::asJson()
                ->timeout(self::REQUEST_TIMEOUT)
                ->get($this->apiUrl('getWebhookInfo'));
        } catch (Throwable $exception) {
            return ['ok' => false, 'description' => $exception->getMessage()];
        }

        return $response->json() ?: [
            'ok' => $response->successful(),
            'description' => $response->body(),
        ];
    }

    public function deleteWebhook(bool $dropPendingUpdates = false): array
    {
        if (! $this->configured()) {
            return ['ok' => false, 'description' => 'TELEGRAM_BOT_TOKEN is not configured.'];
        }

        try {
            $response = Http::asJson()
                ->timeout(self::REQUEST_TIMEOUT)
                ->post($this->apiUrl('deleteWebhook'), [
                    'drop_pending_updates' => $dropPendingUpdates,
                ]);
        } catch (Throwable $exception) {
            return ['ok' => false, 'description' => $exception->getMessage()];
        }

        return $response->json() ?: [
            'ok' => $response->successful(),
            'description' => $response->body(),
        ];
    }

    private function apiUrl(string $method): string
    {
        return 'https://api.telegram.org/bot'.config('services.telegram.bot_token').'/'.$method;
    }
}
