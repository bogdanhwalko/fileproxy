<?php

namespace App\Services;

use App\Models\PhoneAuthChallenge;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PhoneAuthService
{
    private const PHONE_START_PREFIX = 'phone_';
    private const TOKEN_START_PREFIX = 'fileproxy_';

    public function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        return '+'.$digits;
    }

    public function isValidPhone(string $phone): bool
    {
        return (bool) preg_match('/^\+380[0-9]{9}$/', $phone);
    }

    public function createChallenge(string $phone): PhoneAuthChallenge
    {
        PhoneAuthChallenge::where('phone', $phone)
            ->where(function ($query) {
                $query->whereNull('consumed_at')->orWhere('expires_at', '<', now());
            })
            ->delete();

        return PhoneAuthChallenge::create([
            'phone' => $phone,
            'token' => Str::random(48),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(10),
        ]);
    }

    public function telegramPayload(PhoneAuthChallenge $challenge): string
    {
        return self::PHONE_START_PREFIX.ltrim($challenge->phone, '+');
    }

    public function telegramLink(PhoneAuthChallenge $challenge): ?string
    {
        $username = trim((string) config('services.telegram.bot_username'));

        if ($username === '') {
            return null;
        }

        return 'https://t.me/'.ltrim($username, '@').'?start='.$this->telegramPayload($challenge);
    }

    public function localCodeForChallenge(PhoneAuthChallenge $challenge): ?string
    {
        if (! $this->shouldShowCodeLocally()) {
            return null;
        }

        return $this->generateCodeForToken($challenge->token);
    }

    public function shouldShowCodeLocally(): bool
    {
        return (bool) config('services.telegram.show_code_locally')
            && ! app()->environment('production');
    }

    public function generateCodeForPayload(string $payload): ?string
    {
        if (str_starts_with($payload, self::TOKEN_START_PREFIX)) {
            return $this->generateCodeForToken(substr($payload, strlen(self::TOKEN_START_PREFIX)));
        }

        $phone = $this->phoneFromPayload($payload);

        if (! $phone) {
            return null;
        }

        return $this->generateCodeForPhone($phone);
    }

    public function generateCodeForToken(string $token): ?string
    {
        $challenge = PhoneAuthChallenge::where('token', $token)
            ->whereNull('consumed_at')
            ->where('expires_at', '>=', now())
            ->first();

        if (! $challenge) {
            return null;
        }

        $code = (string) random_int(100000, 999999);

        $challenge->forceFill([
            'code_hash' => Hash::make($code),
            'attempts' => 0,
        ])->save();

        return $code;
    }

    public function generateCodeForPhone(string $phone): ?string
    {
        $phone = $this->normalizePhone($phone);

        if (! $this->isValidPhone($phone)) {
            return null;
        }

        $challenge = PhoneAuthChallenge::where('phone', $phone)
            ->whereNull('consumed_at')
            ->where('expires_at', '>=', now())
            ->latest('id')
            ->first();

        if (! $challenge) {
            return null;
        }

        return $this->generateCodeForToken($challenge->token);
    }

    public function phoneFromPayload(string $payload): ?string
    {
        $payload = trim($payload);

        if (str_starts_with($payload, self::PHONE_START_PREFIX)) {
            $payload = substr($payload, strlen(self::PHONE_START_PREFIX));
        }

        $phone = $this->normalizePhone($payload);

        return $this->isValidPhone($phone) ? $phone : null;
    }

    public function verify(string $token, string $phone, string $code): bool
    {
        $challenge = PhoneAuthChallenge::where('token', $token)
            ->where('phone', $phone)
            ->whereNull('consumed_at')
            ->where('expires_at', '>=', now())
            ->first();

        if (! $challenge || ! $challenge->code_hash || $challenge->attempts >= 5) {
            return false;
        }

        if (! Hash::check($code, $challenge->code_hash)) {
            $challenge->increment('attempts');

            return false;
        }

        $challenge->forceFill([
            'consumed_at' => now(),
        ])->save();

        return true;
    }
}
