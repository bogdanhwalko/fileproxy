<?php

namespace App\Console\Commands;

use App\Services\PhoneAuthService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PDO;
use Throwable;

class PhoneAuthDoctorCommand extends Command
{
    protected $signature = 'phone-auth:doctor
        {phone? : Phone number to inspect}
        {--code= : Optional 6-digit code to test}
        {--recent=10 : Number of recent auth rows to inspect}';

    protected $description = 'Show database and phone auth diagnostics without exposing secrets.';

    public function handle(PhoneAuthService $phoneAuth): int
    {
        $connection = (string) config('database.default');
        $config = config("database.connections.{$connection}", []);
        $options = $config['options'] ?? [];

        $this->info('Application');
        $this->table(['Key', 'Value'], [
            ['app_timezone', (string) config('app.timezone')],
            ['app_now', now()->format('Y-m-d H:i:s')],
            ['db_connection', $connection],
            ['configured_driver', (string) ($config['driver'] ?? '')],
            ['configured_charset', (string) ($config['charset'] ?? '')],
            ['configured_collation', (string) ($config['collation'] ?? '')],
            ['emulate_prepares_configured', array_key_exists(PDO::ATTR_EMULATE_PREPARES, $options) ? ($options[PDO::ATTR_EMULATE_PREPARES] ? 'yes' : 'no') : 'not set'],
        ]);

        $this->showPdoDiagnostics();

        $phone = (string) ($this->argument('phone') ?? '');

        if ($phone === '') {
            return self::SUCCESS;
        }

        $phone = $phoneAuth->normalizePhone($phone);

        if (! $phoneAuth->isValidPhone($phone)) {
            $this->error('Invalid phone. Expected +380XXXXXXXXX.');

            return self::FAILURE;
        }

        $code = (string) $this->option('code');

        if ($code !== '' && ! preg_match('/^[0-9]{6}$/', $code)) {
            $this->error('Invalid code. Expected 6 digits.');

            return self::FAILURE;
        }

        $this->showTelegramContacts($phone);
        $this->showChallenges($phone, $code, max(1, min(50, (int) $this->option('recent'))));

        return self::SUCCESS;
    }

    private function showPdoDiagnostics(): void
    {
        try {
            $pdo = DB::connection()->getPdo();

            $this->info('PDO');
            $this->table(['Key', 'Value'], [
                ['driver', $pdo->getAttribute(PDO::ATTR_DRIVER_NAME)],
                ['server_version', $pdo->getAttribute(PDO::ATTR_SERVER_VERSION)],
                ['client_version', $pdo->getAttribute(PDO::ATTR_CLIENT_VERSION)],
                ['emulate_prepares_runtime', $pdo->getAttribute(PDO::ATTR_EMULATE_PREPARES) ? 'yes' : 'no'],
            ]);

            $row = DB::selectOne(
                'select database() as db_name,
                    @@version as db_version,
                    @@version_comment as db_comment,
                    @@character_set_client as charset_client,
                    @@character_set_connection as charset_connection,
                    @@character_set_results as charset_results,
                    @@collation_connection as collation_connection,
                    @@time_zone as db_time_zone,
                    now() as db_now'
            );

            $this->info('Database session');
            $this->table(['Key', 'Value'], collect((array) $row)->map(
                fn ($value, $key): array => [(string) $key, (string) $value]
            )->values()->all());
        } catch (Throwable $exception) {
            $this->error('Database diagnostics failed: '.$exception->getMessage());
        }
    }

    private function showTelegramContacts(string $phone): void
    {
        if (! Schema::hasTable('telegram_auth_contacts')) {
            $this->warn('telegram_auth_contacts table is missing.');

            return;
        }

        $contacts = DB::table('telegram_auth_contacts')
            ->where('phone', $phone)
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        if ($contacts->isEmpty()) {
            $this->warn("No remembered Telegram contacts found for {$phone}.");

            return;
        }

        $this->info('Remembered Telegram contacts');
        $this->table(['ID', 'Telegram user', 'Phone', 'Username', 'Created raw', 'Updated raw'], $contacts->map(fn (object $contact): array => [
            (string) $contact->id,
            (string) $contact->telegram_user_id,
            (string) $contact->phone,
            (string) ($contact->username ?? ''),
            (string) ($contact->created_at ?? ''),
            (string) ($contact->updated_at ?? ''),
        ])->all());
    }

    private function showChallenges(string $phone, string $code, int $limit): void
    {
        if (! Schema::hasTable('phone_auth_challenges')) {
            $this->error('phone_auth_challenges table is missing.');

            return;
        }

        $now = now()->format('Y-m-d H:i:s');
        $rows = DB::table('phone_auth_challenges')
            ->where('phone', $phone)
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        if ($rows->isEmpty()) {
            $this->warn("No phone auth challenges found for {$phone}.");

            return;
        }

        $matchedActive = false;

        $this->info('Phone auth challenges');
        $this->table(['ID', 'Created raw', 'Expires raw', 'Consumed raw', 'Attempts', 'Active', 'Has code', 'Hash algo', 'Matches'], $rows->map(function (object $row) use ($code, $now, &$matchedActive): array {
            $attempts = (int) ($row->attempts ?? 0);
            $expiresAt = (string) ($row->expires_at ?? '');
            $consumedAt = $row->consumed_at ?? null;
            $hasUsableDate = preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $expiresAt) === 1;
            $active = empty($consumedAt) && $hasUsableDate && $expiresAt >= $now && $attempts < 5;
            $hash = (string) ($row->code_hash ?? '');
            $hashInfo = $hash !== '' ? password_get_info($hash) : ['algoName' => 'none'];
            $matches = $code !== '' && $hash !== '' && password_verify($code, $hash);

            if ($matches && $active) {
                $matchedActive = true;
            }

            return [
                (string) $row->id,
                (string) ($row->created_at ?? ''),
                $expiresAt,
                (string) ($consumedAt ?? ''),
                (string) $attempts,
                $active ? 'yes' : 'no',
                $hash !== '' ? 'yes' : 'no',
                (string) ($hashInfo['algoName'] ?? 'unknown'),
                $matches ? 'yes' : ($code !== '' ? 'no' : 'not checked'),
            ];
        })->all());

        if ($code !== '') {
            $matchedActive
                ? $this->info("Code matches an active challenge for {$phone}.")
                : $this->error("Code does not match an active challenge for {$phone}.");
        }
    }
}
