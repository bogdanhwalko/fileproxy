<?php

namespace App\Http\Controllers;

use App\Services\PhoneAuthService;
use App\Services\TelegramBotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TelegramWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        string $secret,
        PhoneAuthService $phoneAuth,
        TelegramBotService $telegram
    ): JsonResponse {
        abort_unless(hash_equals((string) config('services.telegram.webhook_secret'), $secret), 404);

        $message = $request->input('message');

        if (! is_array($message)) {
            return response()->json(['ok' => true]);
        }

        $this->handleMessage($message, $phoneAuth, $telegram);

        return response()->json(['ok' => true]);
    }

    public function handleMessage(array $message, PhoneAuthService $phoneAuth, TelegramBotService $telegram): void
    {
        $chatId = data_get($message, 'chat.id');
        $text = trim((string) data_get($message, 'text', ''));

        if (! $chatId || ! str_starts_with($text, '/start')) {
            return;
        }

        $payload = trim((string) str($text)->after('/start'));

        if ($payload === '') {
            $telegram->sendMessage($chatId, 'Відкрийте FileProxy і перейдіть у бота з посилання, яке сайт покаже після введення телефону.');

            return;
        }

        $code = $phoneAuth->generateCodeForPayload($payload);

        if (! $code) {
            $telegram->sendMessage($chatId, 'Код не створено. Спробуйте ще раз із форми FileProxy.');

            return;
        }

        $telegram->sendMessage($chatId, "Ваш код FileProxy: {$code}\nКод діє 10 хвилин.");
    }
}
