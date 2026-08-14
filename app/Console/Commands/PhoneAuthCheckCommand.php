<?php

namespace App\Console\Commands;

use App\Services\PhoneAuthService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PhoneAuthCheckCommand extends Command
{
    protected $signature = 'phone-auth:check {phone} {code} {--recent=10 : Number of recent rows to inspect}';

    protected $description = 'Check whether a Telegram auth code matches active challenges for a phone number.';

    public function handle(PhoneAuthService $phoneAuth): int
    {
        $phone = $phoneAuth->normalizePhone((string) $this->argument('phone'));
        $code = (string) $this->argument('code');

        if (! $phoneAuth->isValidPhone($phone)) {
            $this->error('Invalid phone. Expected international format, e.g. +380671234567.');

            return self::FAILURE;
        }

        if (! preg_match('/^[0-9]{6}$/', $code)) {
            $this->error('Invalid code. Expected 6 digits.');

            return self::FAILURE;
        }

        $now = now()->format('Y-m-d H:i:s');
        $limit = max(1, min(50, (int) $this->option('recent')));

        $challenges = DB::table('phone_auth_challenges')
            ->where('phone', $phone)
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        if ($challenges->isEmpty()) {
            $this->warn("No challenge rows found for {$phone}.");

            return self::SUCCESS;
        }

        $matchedActive = false;
        $matchedAny = false;

        $this->table(['ID', 'Created raw', 'Expires raw', 'Consumed raw', 'Attempts', 'Active', 'Has code', 'Matches'], $challenges->map(function (object $challenge) use ($code, $now, &$matchedActive, &$matchedAny): array {
            $attempts = (int) ($challenge->attempts ?? 0);
            $expiresAt = (string) ($challenge->expires_at ?? '');
            $consumedAt = $challenge->consumed_at ?? null;
            $hasUsableDate = preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $expiresAt) === 1;
            $isActive = empty($consumedAt)
                && $hasUsableDate
                && $expiresAt >= $now
                && $attempts < 5;

            $matches = ! empty($challenge->code_hash)
                && password_verify($code, (string) $challenge->code_hash);

            if ($matches) {
                $matchedAny = true;
            }

            if ($matches && $isActive) {
                $matchedActive = true;
            }

            return [
                (string) $challenge->id,
                (string) ($challenge->created_at ?? ''),
                $expiresAt,
                (string) ($consumedAt ?? ''),
                (string) $attempts,
                $isActive ? 'yes' : 'no',
                ! empty($challenge->code_hash) ? 'yes' : 'no',
                $matches ? 'yes' : 'no',
            ];
        })->all());

        $matchedActive
            ? $this->info("Code matches an active challenge for {$phone}.")
            : $this->error($matchedAny
                ? "Code matches a row for {$phone}, but that row is not active. Send /start again and use the new code."
                : "Code does not match recent challenge rows for {$phone}.");

        return $matchedActive ? self::SUCCESS : self::FAILURE;
    }
}
