<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class TelegramWebhookCommandTest extends TestCase
{
    public function test_command_sets_authorization_bot_webhook_from_environment(): void
    {
        config([
            'services.telegram.bot_token' => '123456:ABCDEF',
            'services.telegram.webhook_secret' => 'test-secret',
        ]);

        URL::forceRootUrl('https://fileproxy.test');
        URL::forceScheme('https');

        Http::fake([
            'https://api.telegram.org/bot123456:ABCDEF/setWebhook' => Http::response([
                'ok' => true,
                'result' => true,
                'description' => 'Webhook was set',
            ]),
        ]);

        $this->artisan('telegram:webhook', ['action' => 'set'])
            ->expectsOutput('Telegram webhook has been configured.')
            ->expectsOutput('URL: https://fileproxy.test/api/telegram/webhook/test-secret')
            ->assertSuccessful();

        Http::assertSent(fn ($request) => $request->url() === 'https://api.telegram.org/bot123456:ABCDEF/setWebhook'
            && $request['url'] === 'https://fileproxy.test/api/telegram/webhook/test-secret'
            && $request['allowed_updates'] === ['message']
            && $request['drop_pending_updates'] === false);
    }

    public function test_command_fails_without_webhook_secret(): void
    {
        config([
            'services.telegram.bot_token' => '123456:ABCDEF',
            'services.telegram.webhook_secret' => '',
        ]);

        $this->artisan('telegram:webhook', ['action' => 'set'])
            ->expectsOutput('TELEGRAM_WEBHOOK_SECRET is not configured.')
            ->assertFailed();

        Http::assertNothingSent();
    }

    public function test_command_can_read_webhook_info(): void
    {
        config(['services.telegram.bot_token' => '123456:ABCDEF']);

        Http::fake([
            'https://api.telegram.org/bot123456:ABCDEF/getWebhookInfo' => Http::response([
                'ok' => true,
                'result' => [
                    'url' => 'https://fileproxy.test/api/telegram/webhook/test-secret',
                    'pending_update_count' => 0,
                ],
            ]),
        ]);

        $this->artisan('telegram:webhook', ['action' => 'info'])
            ->assertSuccessful();

        Http::assertSent(fn ($request) => $request->url() === 'https://api.telegram.org/bot123456:ABCDEF/getWebhookInfo');
    }
}
