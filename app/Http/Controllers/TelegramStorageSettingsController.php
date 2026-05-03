<?php

namespace App\Http\Controllers;

use App\Models\TelegramBotToken;
use App\Models\TelegramStorageGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TelegramStorageSettingsController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        return view('settings.telegram', [
            'botTokens' => $user->telegramBotTokens()
                ->withCount('storageGroups')
                ->orderByDesc('is_default')
                ->orderBy('name')
                ->get(),
            'globalDefaultGroupsCount' => TelegramStorageGroup::where('is_global_default', true)->count(),
            'storageGroups' => $user->telegramStorageGroups()
                ->with('botToken')
                ->orderByDesc('is_global_default')
                ->orderByDesc('is_default')
                ->orderBy('title')
                ->get(),
        ]);
    }

    public function storeBot(Request $request): RedirectResponse
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

        $user->telegramBotTokens()->create([
            'name' => $validated['name'],
            'username' => $validated['username'] ?? null,
            'token' => $validated['token'],
            'is_default' => $isDefault,
        ]);

        return redirect()
            ->route('telegram-settings.index')
            ->with('status', 'Telegram-бота додано.');
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
