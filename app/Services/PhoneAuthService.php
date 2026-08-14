<?php

namespace App\Services;

use App\Models\PhoneAuthChallenge;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PhoneAuthService
{
    private const TOKEN_START_PREFIX = 'fileproxy_';

    public function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if ($digits === '') {
            return '';
        }

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        // Telegram sometimes hands back a contact's phone number in local Ukrainian
        // format (10 digits, leading trunk 0) instead of the full international one.
        // Only this narrow, historically-supported shape gets a country code assumed;
        // any other input is trusted to already carry its own country code.
        if (strlen($digits) === 10 && str_starts_with($digits, '0')) {
            $digits = '38'.$digits;
        }

        return '+'.$digits;
    }

    /**
     * General E.164 check: a leading '+', country code can't start with 0,
     * and 8-15 digits total — no single country is assumed.
     */
    public function isValidPhone(string $phone): bool
    {
        return (bool) preg_match('/^\+[1-9][0-9]{7,14}$/', $phone);
    }

    public function createChallenge(string $phone): PhoneAuthChallenge
    {
        PhoneAuthChallenge::where('phone', $phone)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => now()]);

        PhoneAuthChallenge::where('phone', $phone)
            ->where('expires_at', '<', now()->subDay())
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
        return self::TOKEN_START_PREFIX.$challenge->token;
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

    /**
     * Extract the raw challenge token from a bot deep-link payload
     * (`fileproxy_<token>`). Returns null for anything else.
     */
    public function tokenFromPayload(string $payload): ?string
    {
        if (! str_starts_with($payload, self::TOKEN_START_PREFIX)) {
            return null;
        }

        return substr($payload, strlen(self::TOKEN_START_PREFIX));
    }

    /**
     * Look up which phone number an active challenge token belongs to,
     * without generating or sending a code. Callers MUST verify the
     * requester actually owns that phone (e.g. via a matching
     * TelegramAuthContact) before calling generateCodeForToken() — knowing
     * the token alone is not proof of ownership, since it's handed out to
     * whoever initiates a login/registration for any phone number.
     */
    public function phoneForToken(string $token): ?string
    {
        return PhoneAuthChallenge::where('token', $token)
            ->whereNull('consumed_at')
            ->where('expires_at', '>=', now())
            ->value('phone');
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

        return $this->generateCodeForChallenge($challenge);
    }

    public function issueCodeForPhone(string $phone): ?string
    {
        $phone = $this->normalizePhone($phone);

        if (! $this->isValidPhone($phone)) {
            return null;
        }

        return $this->generateCodeForChallenge($this->createChallenge($phone));
    }

    public function generateCodeForPhone(string $phone, bool $createIfMissing = true): ?string
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
            if (! $createIfMissing) {
                return null;
            }

            $challenge = $this->createChallenge($phone);
        }

        return $this->generateCodeForToken($challenge->token);
    }

    public function verify(string $token, string $phone, string $code): bool
    {
        $phone = $this->normalizePhone($phone);
        $token = trim($token);

        $activeChallenges = PhoneAuthChallenge::where('phone', $phone)
            ->whereNull('consumed_at')
            ->where('expires_at', '>=', now())
            ->latest('id')
            ->get();

        foreach ($activeChallenges as $challenge) {
            if ($this->challengeCodeMatches($challenge, $code)) {
                $challenge->forceFill([
                    'consumed_at' => now(),
                ])->save();

                $this->consumeOtherChallengesForPhone($phone, $challenge->id);

                return true;
            }
        }

        $failedChallenge = $token !== ''
            ? $activeChallenges->firstWhere('token', $token)
            : $activeChallenges->first();

        if ($failedChallenge) {
            $failedChallenge->increment('attempts');
        }

        return false;
    }

    private function challengeCodeMatches(PhoneAuthChallenge $challenge, string $code): bool
    {
        if (! $challenge || ! $challenge->code_hash || $challenge->attempts >= 5) {
            return false;
        }

        return password_verify($code, (string) $challenge->code_hash);
    }

    private function generateCodeForChallenge(PhoneAuthChallenge $challenge): string
    {
        $code = (string) random_int(100000, 999999);

        $challenge->forceFill([
            'code_hash' => Hash::make($code),
            'attempts' => 0,
        ])->save();

        return $code;
    }

    private function consumeOtherChallengesForPhone(string $phone, int $exceptId): void
    {
        PhoneAuthChallenge::where('phone', $phone)
            ->whereNull('consumed_at')
            ->where('id', '!=', $exceptId)
            ->update(['consumed_at' => now()]);
    }
}
