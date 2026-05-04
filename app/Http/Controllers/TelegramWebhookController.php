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

        if (! $chatId || ! preg_match('/^\/start(?:@[A-Za-z0-9_]+)?(?:\s+(.*))?$/u', $text, $matches)) {
            return;
        }

        $payload = trim((string) ($matches[1] ?? ''));

        if ($payload === '') {
            $telegram->sendMessage($chatId, 'Надішліть команду у форматі /start phone_380XXXXXXXXX або відкрийте бота з посилання, яке FileProxy покаже після введення телефону.');

            return;
        }

        $code = $phoneAuth->generateCodeForPayload($payload);

        if (! $code) {
            $telegram->sendMessage($chatId, 'Код не створено. Перевірте номер телефону або спочатку натисніть "Отримати код" у формі FileProxy.');

            return;
        }

        $telegram->sendMessage($chatId, "Ваш код FileProxy: {$code}\nКод діє 10 хвилин.");
    }
}
