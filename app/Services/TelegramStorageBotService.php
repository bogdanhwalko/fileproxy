<?php

namespace App\Services;

use App\Models\TelegramBotToken;
use Illuminate\Support\Facades\Http;
use Throwable;

class TelegramStorageBotService
{
    private const REQUEST_TIMEOUT = 10;

    public function setWebhook(TelegramBotToken $bot, string $url): bool
    {
        try {
            $response = Http::asJson()
                ->timeout(self::REQUEST_TIMEOUT)
                ->post($this->apiUrl($bot, 'setWebhook'), [
                    'url' => $url,
                    'allowed_updates' => ['message', 'my_chat_member'],
                ]);

            return $response->successful() && (bool) $response->json('ok', false);
        } catch (Throwable) {
            return false;
        }
    }

    public function setMyCommands(TelegramBotToken $bot): bool
    {
        try {
            $response = Http::asJson()
                ->timeout(self::REQUEST_TIMEOUT)
                ->post($this->apiUrl($bot, 'setMyCommands'), [
                    'commands' => [
                        [
                            'command' => 'storage',
                            'description' => 'Прив’язати цю групу до FileProxy-сховища',
                        ],
                    ],
                    'scope' => ['type' => 'all_group_chats'],
                ]);

            return $response->successful() && (bool) $response->json('ok', false);
        } catch (Throwable) {
            return false;
        }
    }

    public function getMe(TelegramBotToken $bot): ?array
    {
        try {
            $response = Http::asJson()
                ->timeout(self::REQUEST_TIMEOUT)
                ->get($this->apiUrl($bot, 'getMe'));
        } catch (Throwable) {
            return null;
        }

        if (! $response->successful() || ! $response->json('ok', false)) {
            return null;
        }

        $result = $response->json('result');

        return is_array($result) ? $result : null;
    }

    public function sendMessage(TelegramBotToken $bot, int|string $chatId, string $text): bool
    {
        try {
            $response = Http::asJson()
                ->timeout(self::REQUEST_TIMEOUT)
                ->post($this->apiUrl($bot, 'sendMessage'), [
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
