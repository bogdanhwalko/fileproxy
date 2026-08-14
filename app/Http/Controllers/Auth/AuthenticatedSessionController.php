<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\PhoneAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request, PhoneAuthService $phoneAuth): RedirectResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:32'],
            'telegram_code' => ['nullable', 'digits:6'],
            'challenge_token' => ['nullable', 'string'],
        ]);

        $phone = $phoneAuth->normalizePhone($validated['phone']);
        $this->validatePhone($phone);

        // Anti-enumeration: never reveal whether the phone exists before code verification.
        // Step 1 (no code yet) — issue a Telegram challenge regardless of account existence.
        // Step 2 (code submitted) — verify the code AND lookup the user; show a generic
        // "wrong number / code / blocked account" message for any failure path.
        if (empty($validated['telegram_code'])) {
            return $this->redirectWithTelegramChallenge($request, $phoneAuth, $phone, 'login');
        }

        $codeValid = $phoneAuth->verify(
            (string) ($validated['challenge_token'] ?? ''),
            $phone,
            $validated['telegram_code']
        );

        $user = User::where('phone', $phone)->first();

        if (! $codeValid || ! $user || $user->is_blocked) {
            throw ValidationException::withMessages([
                'telegram_code' => 'Невірний номер, код або акаунт недоступний.',
            ]);
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('files.index'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    private function redirectWithTelegramChallenge(
        Request $request,
        PhoneAuthService $phoneAuth,
        string $phone,
        string $action
    ): RedirectResponse {
        $challenge = $phoneAuth->createChallenge($phone);
        $localCode = $phoneAuth->localCodeForChallenge($challenge);

        return back()
            ->withInput($request->except(['telegram_code', 'challenge_token']))
            ->with('telegram_auth', [
                'action' => $action,
                'bot_link' => $phoneAuth->telegramLink($challenge),
                'command' => '/start',
                'local_code' => $localCode,
                'token' => $challenge->token,
            ])
            ->with('status', $localCode
                ? 'Код згенеровано в локальному режимі. Введіть його у форму для підтвердження.'
                : 'Відкрийте Telegram-бота, отримайте код і введіть його у форму.');
    }

    private function validatePhone(string $phone): void
    {
        Validator::make(['phone' => $phone], [
            'phone' => ['required', 'regex:/^\+[1-9][0-9]{7,14}$/'],
        ], [
            'phone.regex' => 'Вкажіть номер телефону в міжнародному форматі, наприклад +380671234567.',
        ])->validate();
    }
}
