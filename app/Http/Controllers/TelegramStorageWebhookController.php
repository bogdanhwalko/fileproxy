<?php

namespace App\Http\Controllers;

use App\Models\TelegramBotToken;
use App\Models\TelegramStorageGroup;
use App\Services\TelegramStorageBotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TelegramStorageWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        TelegramBotToken $bot,
        string $secret,
        TelegramStorageBotService $telegram
    ): JsonResponse {
        abort_unless($bot->webhook_secret && hash_equals((string) $bot->webhook_secret, $secret), 404);

        $myChatMember = $request->input('my_chat_member');

        if (is_array($myChatMember)) {
            $this->handleMyChatMember($bot, $myChatMember, $telegram);

            return response()->json(['ok' => true]);
        }

        $message = $request->input('message');

        if (is_array($message)) {
            $this->handleMessage($bot, $message, $telegram);
        }

        return response()->json(['ok' => true]);
    }

    private function handleMyChatMember(TelegramBotToken $bot, array $update, TelegramStorageBotService $telegram): void
    {
        $chatId = data_get($update, 'chat.id');
        $chatType = (string) data_get($update, 'chat.type', '');

        if (! $chatId || ! in_array($chatType, ['group', 'supergroup'], true)) {
            return;
        }

        $oldStatus = (string) data_get($update, 'old_chat_member.status', '');
        $newStatus = (string) data_get($update, 'new_chat_member.status', '');

        $joined = in_array($newStatus, ['member', 'administrator'], true)
            && in_array($oldStatus, ['left', 'kicked', 'restricted', ''], true);

        if (! $joined) {
            return;
        }

        $title = trim((string) data_get($update, 'chat.title', ''));

        if ($title === '') {
            $title = 'Telegram group '.$chatId;
        }

        $created = $this->upsertStorageGroup($bot, (string) $chatId, $title);

        $telegram->sendMessage(
            $bot,
            $chatId,
            $created
                ? '✅ Готово! Цю групу автоматично додано до Telegram-сховищ FileProxy. Тепер її можна обрати під час завантаження файлів.'
                : 'Ця Telegram-група вже є у списку сховищ FileProxy.'
        );
    }

    private function handleMessage(TelegramBotToken $bot, array $message, TelegramStorageBotService $telegram): void
    {
        $text = trim((string) data_get($message, 'text', ''));

        if (! $this->isStorageCommand($text, $bot)) {
            return;
        }

        $chatId = data_get($message, 'chat.id');
        $chatType = (string) data_get($message, 'chat.type', '');

        if (! $chatId) {
            return;
        }

        if (! in_array($chatType, ['group', 'supergroup'], true)) {
            $telegram->sendMessage($bot, $chatId, 'Додайте цього бота в Telegram-групу для файлів. Група додасться автоматично.');

            return;
        }

        $title = trim((string) data_get($message, 'chat.title', ''));

        if ($title === '') {
            $title = 'Telegram group '.$chatId;
        }

        $created = $this->upsertStorageGroup($bot, (string) $chatId, $title);

        $telegram->sendMessage(
            $bot,
            $chatId,
            $created
                ? 'Групу додано до Telegram-сховищ FileProxy. Тепер її можна вибрати під час завантаження файлів.'
                : 'Ця Telegram-група вже є у списку сховищ FileProxy.'
        );
    }

    private function upsertStorageGroup(TelegramBotToken $bot, string $chatId, string $title): bool
    {
        $hasGroups = $bot->user->telegramStorageGroups()->exists();

        $group = TelegramStorageGroup::firstOrNew([
            'user_id' => $bot->user_id,
            'telegram_bot_token_id' => $bot->id,
            'chat_id' => $chatId,
        ]);

        $wasRecentlyCreated = ! $group->exists;

        $group->title = $title;

        if ($wasRecentlyCreated) {
            $group->is_default = ! $hasGroups;
        }

        $group->save();

        return $wasRecentlyCreated;
    }

    private function isStorageCommand(string $text, TelegramBotToken $bot): bool
    {
        $command = strtolower((string) str($text)->before(' ')->before("\n")->before("\t"));

        if ($command === '/storage') {
            return true;
        }

        if (! str_starts_with($command, '/storage@')) {
            return false;
        }

        $username = strtolower(ltrim((string) $bot->username, '@'));

        return $username !== '' && $command === '/storage@'.$username;
    }
}
