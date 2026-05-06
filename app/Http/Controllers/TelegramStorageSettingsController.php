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

        $drainedGroups = $this->drainPendingUpdates($bot, $telegram);

        $webhookConfigured = $telegram->setWebhook(
            $bot,
            route('telegram.storage-webhook', ['bot' => $bot, 'secret' => $bot->webhook_secret])
        );

        $telegram->setMyCommands($bot);

        $command = $bot->username ? "/storage@{$bot->username}" : '/storage';
        $privacyHint = $privacyEnabled
            ? ' Бот у режимі privacy: пишіть у групі саме '.$command.' (з @-згадкою), або вимкніть privacy у @BotFather через /setprivacy → Disable.'
            : '';

        $statusMessage = $webhookConfigured
            ? 'Telegram-бота додано.'
            : 'Telegram-бота додано, але webhook не налаштовано. Перевірте публічний APP_URL і token бота.';

        if ($drainedGroups > 0) {
            $statusMessage .= " Підтягнуто груп з історії: {$drainedGroups}.";
        } elseif ($webhookConfigured) {
            $statusMessage .= ' Додайте його в групу — група автоматично зʼявиться у списку сховищ.'.$privacyHint;
        }

        return redirect()
            ->route('telegram-settings.index')
            ->with('status', $statusMessage);
    }

    private function drainPendingUpdates(TelegramBotToken $bot, TelegramStorageBotService $telegram): int
    {
        $created = 0;
        $offset = null;
        $lastId = null;

        for ($pass = 0; $pass < 5; $pass++) {
            $updates = $telegram->getUpdates($bot, 0, $offset);

            if ($updates === []) {
                break;
            }

            foreach ($updates as $update) {
                $updateId = (int) data_get($update, 'update_id', 0);

                if ($updateId > 0) {
                    $lastId = $updateId;
                }

                $result = $telegram->tryRegisterFromUpdate($bot, $update, false);

                if ($result['created']) {
                    $created++;
                }
            }

            if ($lastId === null) {
                break;
            }

            $offset = $lastId + 1;
        }

        if ($lastId !== null) {
            $telegram->getUpdates($bot, 0, $lastId + 1);
        }

        return $created;
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

    public function syncBot(TelegramBotToken $bot, Request $request, TelegramStorageBotService $telegram)
    {
        abort_unless((int) $bot->user_id === (int) auth()->id(), 404);

        $telegram->deleteWebhook($bot, false);

        $createdGroups = 0;
        $processedUpdates = 0;
        $lastUpdateId = null;
        $offset = null;

        for ($pass = 0; $pass < 5; $pass++) {
            $updates = $telegram->getUpdates($bot, 0, $offset);

            if ($updates === []) {
                break;
            }

            foreach ($updates as $update) {
                $processedUpdates++;
                $updateId = (int) data_get($update, 'update_id', 0);

                if ($updateId > 0) {
                    $lastUpdateId = $updateId;
                }

                $result = $telegram->tryRegisterFromUpdate($bot, $update, true);

                if ($result['created']) {
                    $createdGroups++;
                }
            }

            if ($lastUpdateId === null) {
                break;
            }

            $offset = $lastUpdateId + 1;
        }

        if ($lastUpdateId !== null) {
            $telegram->getUpdates($bot, 0, $lastUpdateId + 1);
        }

        $webhookUrl = route('telegram.storage-webhook', [
            'bot' => $bot,
            'secret' => $bot->webhook_secret,
        ]);
        $telegram->setWebhook($bot, $webhookUrl);
        $telegram->setMyCommands($bot);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'created_groups' => $createdGroups,
                'processed_updates' => $processedUpdates,
            ]);
        }

        $message = "Синхронізацію бота «{$bot->name}» завершено. ";
        $message .= "Опрацьовано подій: {$processedUpdates}. ";
        $message .= $createdGroups > 0
            ? "Додано нових груп: {$createdGroups}."
            : 'Нових груп не знайдено. Якщо очікуєте додавання — переконайтесь, що бот реально доданий у групу як учасник.';

        return redirect()
            ->route('telegram-settings.index')
            ->with('status', $message);
    }

    public function repairBot(TelegramBotToken $bot, TelegramStorageBotService $telegram): RedirectResponse
    {
        abort_unless((int) $bot->user_id === (int) auth()->id(), 404);

        $info = $telegram->getMe($bot);

        if (is_array($info)) {
            $apiUsername = $this->normalizeUsername((string) data_get($info, 'username', ''));

            if ($apiUsername && $apiUsername !== (string) $bot->username) {
                $bot->forceFill(['username' => $apiUsername])->save();
            }
        }

        $webhookUrl = route('telegram.storage-webhook', [
            'bot' => $bot,
            'secret' => $bot->webhook_secret,
        ]);

        $webhookOk = $telegram->setWebhook($bot, $webhookUrl);
        $telegram->setMyCommands($bot);

        if (! $webhookOk) {
            $webhookInfo = $telegram->getWebhookInfo($bot);
            $lastError = data_get($webhookInfo, 'last_error_message');

            $message = "Не вдалося оновити webhook для «{$bot->name}». ";

            if (! str_starts_with($webhookUrl, 'https://')) {
                $message .= 'Telegram приймає тільки HTTPS-URL, а у вас APP_URL не https. Виправте APP_URL у .env та зверніться до адміністратора.';
            } elseif ($lastError) {
                $message .= "Telegram повернув помилку: {$lastError}";
            } else {
                $message .= 'Перевірте, що сайт доступний з інтернету за HTTPS і token бота правильний.';
            }

            return redirect()
                ->route('telegram-settings.index')
                ->withErrors(['webhook' => $message]);
        }

        $afterInfo = $telegram->getWebhookInfo($bot);
        $allowedUpdates = data_get($afterInfo, 'allowed_updates');
        $hasMyChatMember = is_array($allowedUpdates) && in_array('my_chat_member', $allowedUpdates, true);
        $lastError = data_get($afterInfo, 'last_error_message');

        $status = "Webhook бота «{$bot->name}» оновлено.";

        if (! $hasMyChatMember) {
            $status .= ' Увага: Telegram ще не підтвердив підписку на події приєднання — спробуйте ще раз через 10 секунд.';
        } else {
            $status .= ' Якщо бот уже в групі, виключіть його і додайте знову — група зʼявиться у списку сховищ автоматично.';
        }

        if ($lastError) {
            $status .= " Остання помилка від Telegram: {$lastError}";
        }

        return redirect()
            ->route('telegram-settings.index')
            ->with('status', $status);
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
