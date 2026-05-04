<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\PhoneAuthService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request, PhoneAuthService $phoneAuth): RedirectResponse
    {
        $this->ensureRegistrationSchemaIsReady();

        $validated = $request->validate([
            'nickname' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:32'],
            'telegram_code' => ['nullable', 'digits:6'],
            'challenge_token' => ['nullable', 'string'],
        ]);

        $phone = $phoneAuth->normalizePhone($validated['phone']);
        $this->validatePhoneForRegistration($phone);

        if (empty($validated['telegram_code'])) {
            return $this->redirectWithTelegramChallenge($request, $phoneAuth, $phone, 'register');
        }

        if (! $phoneAuth->verify((string) ($validated['challenge_token'] ?? ''), $phone, $validated['telegram_code'])) {
            throw ValidationException::withMessages([
                'telegram_code' => 'Невірний або прострочений код Telegram.',
            ]);
        }

        $attributes = [
            'name' => $validated['nickname'],
            'phone' => $phone,
            'email' => $this->technicalEmail($phone),
            'password' => Hash::make(Str::random(48)),
        ];

        if (Schema::hasColumn('users', 'is_admin')) {
            $attributes['is_admin'] = ! User::where('is_admin', true)->exists();
        }

        try {
            $user = User::create($attributes);
        } catch (QueryException $exception) {
            Log::error('User registration failed.', [
                'phone' => $phone,
                'error' => $exception->getMessage(),
            ]);

            throw ValidationException::withMessages([
                'phone' => 'Не вдалося створити користувача. Перевірте, що на сервері виконано php artisan migrate --force.',
            ]);
        }

        event(new Registered($user));

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('files.index');
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

    private function validatePhoneForRegistration(string $phone): void
    {
        Validator::make(['phone' => $phone], [
            'phone' => ['required', 'regex:/^\+380[0-9]{9}$/', 'unique:users,phone'],
        ], [
            'phone.regex' => 'Вкажіть номер телефону у форматі +380XXXXXXXXX.',
            'phone.unique' => 'Користувач із таким номером телефону вже існує.',
        ])->validate();
    }

    private function technicalEmail(string $phone): string
    {
        return preg_replace('/\D+/', '', $phone).'@phone.fileproxy.local';
    }

    private function ensureRegistrationSchemaIsReady(): void
    {
        $requiredColumns = ['name', 'phone', 'email', 'password'];

        if (! Schema::hasTable('users')) {
            throw ValidationException::withMessages([
                'phone' => 'База даних не підготовлена: таблиця users відсутня. Виконайте php artisan migrate --force.',
            ]);
        }

        foreach ($requiredColumns as $column) {
            if (! Schema::hasColumn('users', $column)) {
                throw ValidationException::withMessages([
                    'phone' => "База даних не підготовлена: у таблиці users відсутня колонка {$column}. Виконайте php artisan migrate --force.",
                ]);
            }
        }
    }
}
