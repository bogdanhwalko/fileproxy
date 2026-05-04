<?php

namespace App\Console\Commands;

use App\Models\PhoneAuthChallenge;
use App\Services\PhoneAuthService;
use Illuminate\Console\Command;

class PhoneAuthCheckCommand extends Command
{
    protected $signature = 'phone-auth:check {phone} {code}';

    protected $description = 'Check whether a Telegram auth code matches active challenges for a phone number.';

    public function handle(PhoneAuthService $phoneAuth): int
    {
        $phone = $phoneAuth->normalizePhone((string) $this->argument('phone'));
        $code = (string) $this->argument('code');

        if (! $phoneAuth->isValidPhone($phone)) {
            $this->error('Invalid phone. Expected +380XXXXXXXXX.');

            return self::FAILURE;
        }

        if (! preg_match('/^[0-9]{6}$/', $code)) {
            $this->error('Invalid code. Expected 6 digits.');

            return self::FAILURE;
        }

        $challenges = PhoneAuthChallenge::where('phone', $phone)
            ->whereNull('consumed_at')
            ->where('expires_at', '>=', now())
            ->latest('id')
            ->get();

        if ($challenges->isEmpty()) {
            $this->warn("No active challenges found for {$phone}.");

            return self::SUCCESS;
        }

        $matched = false;

        $this->table(['ID', 'Created', 'Expires', 'Attempts', 'Has code', 'Matches'], $challenges->map(function (PhoneAuthChallenge $challenge) use ($code, &$matched): array {
            $matches = $challenge->code_hash
                && (int) $challenge->attempts < 5
                && password_verify($code, (string) $challenge->code_hash);

            if ($matches) {
                $matched = true;
            }

            return [
                (string) $challenge->id,
                optional($challenge->created_at)->format('Y-m-d H:i:s'),
                optional($challenge->expires_at)->format('Y-m-d H:i:s'),
                (string) $challenge->attempts,
                $challenge->code_hash ? 'yes' : 'no',
                $matches ? 'yes' : 'no',
            ];
        })->all());

        $matched
            ? $this->info("Code matches an active challenge for {$phone}.")
            : $this->error("Code does not match active challenges for {$phone}.");

        return $matched ? self::SUCCESS : self::FAILURE;
    }
}
