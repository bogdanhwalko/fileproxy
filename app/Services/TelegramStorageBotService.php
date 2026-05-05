<?php

namespace App\Services;

use App\Models\TelegramBotToken;
use App\Models\TelegramStorageGroup;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class TelegramStorageBotService
{
    private const REQUEST_TIMEOUT = 10;

    /**
     * Process a single Telegram update. Auto-creates a TelegramStorageGroup
     * when bot is added to a group (my_chat_member) or when /storage is sent.
     *
     * @return array{handled: bool, created: bool, group: ?TelegramStorageGroup, reason: string}
     */
    public function tryRegisterFromUpdate(TelegramBotToken $bot, array $update, bool $sendReply = true): array
    {
        $myChatMember = $update['my_chat_member'] ?? null;

        if (is_array($myChatMember)) {
            return $this->handleChatMemberJoin($bot, $myChatMember, $sendReply);
        }

        $message = $update['message'] ?? null;

        if (is_array($message)) {
            return $this->handleStorageCommand($bot, $message, $sendReply);
        }

        return ['handled' => false, 'created' => false, 'group' => null, 'reason' => 'irrelevant_update'];
    }

    private function handleChatMemberJoin(TelegramBotToken $bot, array $update, bool $sendReply): array
    {
        $chatId = data_get($update, 'chat.id');
        $chatType = (string) data_get($update, 'chat.type', '');

        if (! $chatId || ! in_array($chatType, ['group', 'supergroup'], true)) {
            return ['handled' => false, 'created' => false, 'group' => null, 'reason' => 'not_group_chat'];
        }

        $oldStatus = (string) data_get($update, 'old_chat_member.status', '');
        $newStatus = (string) data_get($update, 'new_chat_member.status', '');

        $joined = in_array($newStatus, ['member', 'administrator'], true)
            && in_array($oldStatus, ['left', 'kicked', 'restricted', ''], true);

        if (! $joined) {
            return ['handled' => false, 'created' => false, 'group' => null, 'reason' => 'no_status_change'];
        }

        $title = trim((string) data_get($update, 'chat.title', '')) ?: 'Telegram group '.$chatId;
        [$group, $created] = $this->upsertStorageGroup($bot, (string) $chatId, $title);

        if ($sendReply) {
            $this->sendMessage(
                $bot,
                $chatId,
                $created
                    ? '✅ Готово! Цю групу автоматично додано до Telegram-сховищ FileProxy. Тепер її можна обрати під час завантаження файлів.'
                    : 'Ця Telegram-група вже є у списку сховищ FileProxy.'
            );
        }

        return ['handled' => true, 'created' => $created, 'group' => $group, 'reason' => 'joined'];
    }

    private function handleStorageCommand(TelegramBotToken $bot, array $message, bool $sendReply): array
    {
        $text = trim((string) data_get($message, 'text', ''));
        $command = strtolower((string) preg_split('/[\s\n\t]/', $text)[0] ?? '');

        if ($command !== '/storage') {
            $username = strtolower(ltrim((string) $bot->username, '@'));

            if (! $username || $command !== '/storage@'.$username) {
                return ['handled' => false, 'created' => false, 'group' => null, 'reason' => 'not_storage_command'];
            }
        }

        $chatId = data_get($message, 'chat.id');
        $chatType = (string) data_get($message, 'chat.type', '');

        if (! $chatId) {
            return ['handled' => false, 'created' => false, 'group' => null, 'reason' => 'no_chat_id'];
        }

        if (! in_array($chatType, ['group', 'supergroup'], true)) {
            if ($sendReply) {
                $this->sendMessage($bot, $chatId, 'Додайте цього бота в Telegram-групу — група додасться автоматично.');
            }

            return ['handled' => true, 'created' => false, 'group' => null, 'reason' => 'private_chat'];
        }

        $title = trim((string) data_get($message, 'chat.title', '')) ?: 'Telegram group '.$chatId;
        [$group, $created] = $this->upsertStorageGroup($bot, (string) $chatId, $title);

        if ($sendReply) {
            $this->sendMessage(
                $bot,
                $chatId,
                $created
                    ? 'Групу додано до Telegram-сховищ FileProxy. Тепер її можна вибрати під час завантаження файлів.'
                    : 'Ця Telegram-група вже є у списку сховищ FileProxy.'
            );
        }

        return ['handled' => true, 'created' => $created, 'group' => $group, 'reason' => 'command'];
    }

    /**
     * @return array{0: TelegramStorageGroup, 1: bool}
     */
    private function upsertStorageGroup(TelegramBotToken $bot, string $chatId, string $title): array
    {
        $hasGroups = $bot->user->telegramStorageGroups()->exists();

        $group = TelegramStorageGroup::firstOrNew([
            'user_id' => $bot->user_id,
            'telegram_bot_token_id' => $bot->id,
            'chat_id' => $chatId,
        ]);

        $created = ! $group->exists;
        $group->title = $title;

        if ($created) {
            $group->is_default = ! $hasGroups;
        }

        $group->save();

        Log::info('FileProxy: storage group upserted', [
            'bot_id' => $bot->id,
            'chat_id' => $chatId,
            'created' => $created,
        ]);

        return [$group, $created];
    }

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

    public function deleteWebhook(TelegramBotToken $bot, bool $dropPending = false): bool
    {
        try {
            $response = Http::asJson()
                ->timeout(self::REQUEST_TIMEOUT)
                ->post($this->apiUrl($bot, 'deleteWebhook'), [
                    'drop_pending_updates' => $dropPending,
                ]);

            return $response->successful() && (bool) $response->json('ok', false);
        } catch (Throwable) {
            return false;
        }
    }

    public function getUpdates(TelegramBotToken $bot, int $timeout = 0, ?int $offset = null): array
    {
        try {
            $payload = array_filter([
                'timeout' => $timeout,
                'offset' => $offset,
                'allowed_updates' => ['message', 'my_chat_member'],
            ], fn ($v) => $v !== null);

            $response = Http::asJson()
                ->timeout($timeout + 5)
                ->post($this->apiUrl($bot, 'getUpdates'), $payload);
        } catch (Throwable) {
            return [];
        }

        if (! $response->successful() || ! $response->json('ok', false)) {
            return [];
        }

        $result = $response->json('result');

        return is_array($result) ? $result : [];
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
