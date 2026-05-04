<?php

namespace App\Console\Commands;

use App\Services\TelegramBotService;
use Illuminate\Console\Command;

class TelegramWebhookCommand extends Command
{
    protected $signature = 'telegram:webhook
        {action=set : set, info or delete}
        {--url= : Override webhook URL}
        {--allow-http : Allow non-HTTPS webhook URL}
        {--drop-pending-updates : Drop pending Telegram updates}';

    protected $description = 'Manage the Telegram authorization bot webhook.';

    public function handle(TelegramBotService $telegram): int
    {
        if (! $telegram->configured()) {
            $this->error('TELEGRAM_BOT_TOKEN is not configured.');

            return self::FAILURE;
        }

        return match (strtolower((string) $this->argument('action'))) {
            'set' => $this->setWebhook($telegram),
            'info' => $this->showWebhookInfo($telegram),
            'delete' => $this->deleteWebhook($telegram),
            default => $this->invalidAction(),
        };
    }

    private function setWebhook(TelegramBotService $telegram): int
    {
        $url = $this->option('url') ?: $this->defaultWebhookUrl();

        if (! $url) {
            $this->error('TELEGRAM_WEBHOOK_SECRET is not configured.');

            return self::FAILURE;
        }

        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            $this->error("Webhook URL is invalid: {$url}");

            return self::FAILURE;
        }

        if (! $this->option('allow-http') && ! str_starts_with($url, 'https://')) {
            $this->error('Telegram webhook URL must use HTTPS. Use --allow-http only for local testing.');

            return self::FAILURE;
        }

        $response = $telegram->setWebhook($url, (bool) $this->option('drop-pending-updates'));

        if (! ($response['ok'] ?? false)) {
            $this->error($response['description'] ?? 'Telegram rejected the webhook.');

            return self::FAILURE;
        }

        $this->info('Telegram webhook has been configured.');
        $this->line("URL: {$url}");

        return self::SUCCESS;
    }

    private function showWebhookInfo(TelegramBotService $telegram): int
    {
        $response = $telegram->getWebhookInfo();

        if (! ($response['ok'] ?? false)) {
            $this->error($response['description'] ?? 'Could not read Telegram webhook info.');

            return self::FAILURE;
        }

        $result = $response['result'] ?? [];

        $this->table(['Field', 'Value'], [
            ['url', $result['url'] ?? ''],
            ['pending_update_count', (string) ($result['pending_update_count'] ?? 0)],
            ['last_error_date', isset($result['last_error_date']) ? date('Y-m-d H:i:s', (int) $result['last_error_date']) : ''],
            ['last_error_message', $result['last_error_message'] ?? ''],
        ]);

        return self::SUCCESS;
    }

    private function deleteWebhook(TelegramBotService $telegram): int
    {
        $response = $telegram->deleteWebhook((bool) $this->option('drop-pending-updates'));

        if (! ($response['ok'] ?? false)) {
            $this->error($response['description'] ?? 'Could not delete Telegram webhook.');

            return self::FAILURE;
        }

        $this->info('Telegram webhook has been deleted.');

        return self::SUCCESS;
    }

    private function invalidAction(): int
    {
        $this->error('Invalid action. Use one of: set, info, delete.');

        return self::FAILURE;
    }

    private function defaultWebhookUrl(): ?string
    {
        $secret = trim((string) config('services.telegram.webhook_secret'));

        if ($secret === '') {
            return null;
        }

        return route('telegram.webhook', ['secret' => $secret]);
    }
}
