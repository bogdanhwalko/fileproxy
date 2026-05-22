<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Laravel\Sanctum\PersonalAccessToken;

class ApiTokenController extends Controller
{
    private const TOKEN_LIMIT_PER_USER = 10;

    public function index(Request $request): View
    {
        $user = $request->user();

        return view('settings.api-tokens', [
            'tokens' => $user->tokens()->latest()->get(),
            'newToken' => $request->session()->pull('new_api_token'),
            'newTokenName' => $request->session()->pull('new_api_token_name'),
            'tokenLimit' => self::TOKEN_LIMIT_PER_USER,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->tokens()->count() >= self::TOKEN_LIMIT_PER_USER) {
            return back()->withErrors([
                'name' => 'Досягнуто ліміт у '.self::TOKEN_LIMIT_PER_USER.' активних токенів. Відкличте старі, щоб створити новий.',
            ]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ], [
            'name.required' => 'Вкажіть назву токена (наприклад, «mobile-app» або «backup-script»).',
        ]);

        $token = $user->createToken($validated['name']);

        return redirect()
            ->route('api-tokens.index')
            ->with('new_api_token', $token->plainTextToken)
            ->with('new_api_token_name', $validated['name'])
            ->with('status', 'Токен створено. Збережіть його — більше показано не буде.');
    }

    public function destroy(Request $request, int $token): RedirectResponse
    {
        $deleted = PersonalAccessToken::query()
            ->where('id', $token)
            ->where('tokenable_id', $request->user()->id)
            ->where('tokenable_type', $request->user()::class)
            ->delete();

        if ($deleted === 0) {
            return back()->withErrors(['token' => 'Токен не знайдено.']);
        }

        return back()->with('status', 'Токен відкликано.');
    }
}
