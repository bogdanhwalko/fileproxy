<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class TelegramBotService
{
    public function configured(): bool
    {
        return trim((string) config('services.telegram.bot_token')) !== '';
    }

    public function sendMessage(int|string $chatId, string $text): bool
    {
        if (! $this->configured()) {
            return false;
        }

        $response = Http::asJson()->post($this->apiUrl('sendMessage'), [
            'chat_id' => $chatId,
            'text' => $text,
        ]);

        return $response->successful();
    }

    public function getUpdates(?int $offset = null, int $timeout = 25): array
    {
        if (! $this->configured()) {
            return [];
        }

        $response = Http::asJson()
            ->timeout($timeout + 5)
            ->get($this->apiUrl('getUpdates'), array_filter([
                'offset' => $offset,
                'timeout' => $timeout,
            ], fn ($value) => $value !== null));

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

        $response = Http::asJson()->post($this->apiUrl('setWebhook'), [
            'url' => $url,
            'allowed_updates' => ['message'],
            'drop_pending_updates' => $dropPendingUpdates,
        ]);

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

        $response = Http::asJson()->get($this->apiUrl('getWebhookInfo'));

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

        $response = Http::asJson()->post($this->apiUrl('deleteWebhook'), [
            'drop_pending_updates' => $dropPendingUpdates,
        ]);

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
