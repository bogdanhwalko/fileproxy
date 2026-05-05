<?php

namespace App\Http\Controllers;

use App\Models\ManagedFile;
use App\Models\TelegramBotToken;
use App\Models\TelegramStorageGroup;
use App\Services\TelegramStorageBotService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TelegramStorageSettingsController extends Controller
{
    private const SYSTEM_TELEGRAM_UPLOAD_LIMIT = 100;

    public function index(Request $request): View
    {
        $user = $request->user();
        $storageGroups = $user->telegramStorageGroups()
            ->with('botToken')
            ->orderByDesc('is_global_default')
            ->orderByDesc('is_default')
            ->orderBy('title')
            ->get();
        $globalDefaultGroupIds = TelegramStorageGroup::where('is_global_default', true)->pluck('id');
        $systemTelegramUsedUploads = $globalDefaultGroupIds->isEmpty()
            ? 0
            : (int) ManagedFile::query()
                ->where('user_id', $user->id)
                ->where('storage_driver', 'telegram')
                ->whereIn('telegram_storage_group_id', $globalDefaultGroupIds)
                ->count();

        return view('settings.telegram', [
            'botFatherNewBotUrl' => 'https://telegram.me/botfather',
            'botTokens' => $user->telegramBotTokens()
                ->withCount('storageGroups')
                ->orderByDesc('is_default')
                ->orderBy('name')
                ->get(),
            'globalDefaultGroupsCount' => $globalDefaultGroupIds->count(),
            'storageGroups' => $storageGroups,
            'systemTelegramRemainingUploads' => max(0, self::SYSTEM_TELEGRAM_UPLOAD_LIMIT - $systemTelegramUsedUploads),
            'systemTelegramStorageAvailable' => $globalDefaultGroupIds->isNotEmpty()
                && $systemTelegramUsedUploads < self::SYSTEM_TELEGRAM_UPLOAD_LIMIT,
            'systemTelegramUploadLimit' => self::SYSTEM_TELEGRAM_UPLOAD_LIMIT,
            'systemTelegramUsedUploads' => $systemTelegramUsedUploads,
        ]);
    }

    public function storeBot(Request $request, TelegramStorageBotService $telegram): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'username' => ['nullable', 'string', 'max:100'],
            'token' => ['required', 'string', 'max:255'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $isDefault = $request->boolean('is_default') || ! $user->telegramBotTokens()->exists();

        if ($isDefault) {
            $user->telegramBotTokens()->update(['is_default' => false]);
        }

        $bot = $user->telegramBotTokens()->create([
            'name' => $validated['name'],
            'username' => $this->normalizeUsername($validated['username'] ?? null),
            'token' => $validated['token'],
            'webhook_secret' => Str::random(48),
            'is_default' => $isDefault,
        ]);

        $botInfo = $telegram->getMe($bot);
        $privacyEnabled = false;

        if (is_array($botInfo)) {
            $apiUsername = $this->normalizeUsername((string) data_get($botInfo, 'username', ''));

            if ($apiUsername && $apiUsername !== $bot->username) {
                $bot->forceFill(['username' => $apiUsername])->save();
            }

            $privacyEnabled = ! (bool) data_get($botInfo, 'can_read_all_group_messages', true);
        }

        $webhookConfigured = $telegram->setWebhook(
            $bot,
            route('telegram.storage-webhook', ['bot' => $bot, 'secret' => $bot->webhook_secret])
        );

        $telegram->setMyCommands($bot);

        $command = $bot->username ? "/storage@{$bot->username}" : '/storage';
        $privacyHint = $privacyEnabled
            ? ' Бот у режимі privacy: пишіть у групі саме '.$command.' (з @-згадкою), або вимкніть privacy у @BotFather через /setprivacy → Disable.'
            : '';

        return redirect()
            ->route('telegram-settings.index')
            ->with('status', $webhookConfigured
                ? 'Telegram-бота додано. Додайте його в групу і напишіть там '.$command.', щоб група автоматично зʼявилась у сховищах.'.$privacyHint
                : 'Telegram-бота додано, але webhook не налаштовано. Перевірте публічний APP_URL і token бота. Після цього перездайте бота або налаштуйте webhook вручну.');
    }

    private function normalizeUsername(?string $username): ?string
    {
        $username = trim((string) $username);

        if ($username === '') {
            return null;
        }

        return ltrim($username, '@');
    }

    public function destroyBot(TelegramBotToken $bot): RedirectResponse
    {
        abort_unless((int) $bot->user_id === (int) auth()->id(), 404);

        $bot->delete();

        return redirect()
            ->route('telegram-settings.index')
            ->with('status', 'Telegram-бота видалено.');
    }

    public function makeDefaultBot(TelegramBotToken $bot): RedirectResponse
    {
        abort_unless((int) $bot->user_id === (int) auth()->id(), 404);

        $bot->user->telegramBotTokens()->update(['is_default' => false]);
        $bot->forceFill(['is_default' => true])->save();

        return redirect()
            ->route('telegram-settings.index')
            ->with('status', 'Основного Telegram-бота оновлено.');
    }

    public function storeGroup(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'telegram_bot_token_id' => [
                'required',
                'integer',
                Rule::exists('telegram_bot_tokens', 'id')->where('user_id', $user->id),
            ],
            'title' => ['required', 'string', 'max:100'],
            'chat_id' => ['required', 'string', 'max:128'],
            'is_default' => ['nullable', 'boolean'],
        ], [
            'telegram_bot_token_id.exists' => 'Обраний бот недоступний.',
        ]);

        $isDefault = $request->boolean('is_default') || ! $user->telegramStorageGroups()->exists();

        if ($isDefault) {
            $user->telegramStorageGroups()->update(['is_default' => false]);
        }

        $user->telegramStorageGroups()->create([
            'telegram_bot_token_id' => $validated['telegram_bot_token_id'],
            'title' => $validated['title'],
            'chat_id' => $validated['chat_id'],
            'is_default' => $isDefault,
        ]);

        return redirect()
            ->route('telegram-settings.index')
            ->with('status', 'Telegram-групу додано.');
    }

    public function destroyGroup(TelegramStorageGroup $group): RedirectResponse
    {
        abort_unless((int) $group->user_id === (int) auth()->id(), 404);

        $group->delete();

        return redirect()
            ->route('telegram-settings.index')
            ->with('status', 'Telegram-групу видалено.');
    }

    public function makeDefaultGroup(TelegramStorageGroup $group): RedirectResponse
    {
        abort_unless((int) $group->user_id === (int) auth()->id(), 404);

        $group->user->telegramStorageGroups()->update(['is_default' => false]);
        $group->forceFill(['is_default' => true])->save();

        return redirect()
            ->route('telegram-settings.index')
            ->with('status', 'Основну Telegram-групу оновлено.');
    }

    public function makeGlobalDefaultGroup(TelegramStorageGroup $group): RedirectResponse
    {
        $this->authorizeSystemGroupChange($group);

        $group->forceFill(['is_global_default' => true])->save();

        return redirect()
            ->route('telegram-settings.index')
            ->with('status', 'Групу додано до системних сховищ.');
    }

    public function removeGlobalDefaultGroup(TelegramStorageGroup $group): RedirectResponse
    {
        $this->authorizeSystemGroupChange($group);

        $group->forceFill(['is_global_default' => false])->save();

        return redirect()
            ->route('telegram-settings.index')
            ->with('status', 'Групу прибрано із системних сховищ.');
    }

    private function authorizeSystemGroupChange(TelegramStorageGroup $group): void
    {
        abort_unless(auth()->user()?->is_admin, 403);
        abort_unless((int) $group->user_id === (int) auth()->id(), 404);
    }
}
