<?php

namespace App\Console\Commands;

use App\Http\Controllers\TelegramWebhookController;
use App\Services\PhoneAuthService;
use App\Services\TelegramBotService;
use Illuminate\Console\Command;

class TelegramPollCommand extends Command
{
    protected $signature = 'telegram:poll {--once : Process one polling response and exit}';

    protected $description = 'Poll Telegram updates and issue FileProxy login codes.';

    public function handle(
        PhoneAuthService $phoneAuth,
        TelegramBotService $telegram,
        TelegramWebhookController $handler
    ): int {
        if (! $telegram->configured()) {
            $this->error('TELEGRAM_BOT_TOKEN is not configured.');

            return self::FAILURE;
        }

        $offset = null;

        do {
            $updates = $telegram->getUpdates($offset);

            foreach ($updates as $update) {
                $offset = ((int) $update['update_id']) + 1;

                if (isset($update['message']) && is_array($update['message'])) {
                    $handler->handleMessage($update['message'], $phoneAuth, $telegram);
                }
            }
        } while (! $this->option('once'));

        return self::SUCCESS;
    }
}
