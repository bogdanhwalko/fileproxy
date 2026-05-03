<?php

namespace App\Services;

use App\Models\TelegramBotToken;
use Illuminate\Support\Facades\Http;
use Throwable;

class TelegramStorageBotService
{
    public function setWebhook(TelegramBotToken $bot, string $url): bool
    {
        try {
            $response = Http::asJson()->post($this->apiUrl($bot, 'setWebhook'), [
                'url' => $url,
                'allowed_updates' => ['message'],
            ]);

            return $response->successful() && (bool) $response->json('ok', false);
        } catch (Throwable) {
            return false;
        }
    }

    public function sendMessage(TelegramBotToken $bot, int|string $chatId, string $text): bool
    {
        try {
            $response = Http::asJson()->post($this->apiUrl($bot, 'sendMessage'), [
                'chat_id' => $chatId,
                'text' => $text,
            ]);

            return $response->successful() && (bool) $response->json('ok', false);
        } catch (Throwable) {
            return false;
        }
    }

    private function apiUrl(TelegramBotToken $bot, string $method): string
    {
        return 'https://api.telegram.org/bot'.$bot->token.'/'.$method;
    }
}
