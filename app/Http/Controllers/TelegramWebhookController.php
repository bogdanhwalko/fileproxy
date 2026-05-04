<?php

namespace App\Http\Controllers;

use App\Models\TelegramAuthContact;
use App\Services\PhoneAuthService;
use App\Services\TelegramBotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

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

        if (! $chatId) {
            return;
        }

        $contact = data_get($message, 'contact');

        if (is_array($contact)) {
            $this->handleContact($message, $contact, $phoneAuth, $telegram);

            return;
        }

        if (! preg_match('/^\/start(?:@[A-Za-z0-9_]+)?(?:\s+(.*))?$/u', $text, $matches)) {
            return;
        }

        $payload = trim((string) ($matches[1] ?? ''));

        if ($payload === '') {
            $telegramUserId = data_get($message, 'from.id');
            $knownContact = $telegramUserId && Schema::hasTable('telegram_auth_contacts')
                ? TelegramAuthContact::where('telegram_user_id', (string) $telegramUserId)->first()
                : null;

            if ($knownContact) {
                $phone = $phoneAuth->normalizePhone((string) $knownContact->phone);

                if ($phoneAuth->isValidPhone($phone)) {
                    $this->issueCodeForPhone($phone, $phoneAuth, $telegram, $chatId);

                    return;
                }

                $knownContact->delete();
                $telegram->sendMessage($chatId, 'Збережений номер невалідний. Поділіться контактом ще раз.');
                $this->requestContact($telegram, $chatId);

                return;
            }

            $this->requestContact($telegram, $chatId);

            return;
        }

        $code = $phoneAuth->generateCodeForPayload($payload);

        if (! $code) {
            $this->requestContact($telegram, $chatId);

            return;
        }

        $this->sendCode($telegram, $chatId, $code, $phoneAuth->phoneFromPayload($payload));
    }

    private function handleContact(
        array $message,
        array $contact,
        PhoneAuthService $phoneAuth,
        TelegramBotService $telegram
    ): void {
        $chatId = data_get($message, 'chat.id');
        $senderId = data_get($message, 'from.id');
        $contactUserId = data_get($contact, 'user_id');

        if ($senderId && $contactUserId && (string) $senderId !== (string) $contactUserId) {
            $telegram->sendMessage($chatId, 'Поділіться саме своїм контактом через кнопку нижче.');
            $this->requestContact($telegram, $chatId);

            return;
        }

        $phone = $phoneAuth->normalizePhone($this->contactPhoneNumber($contact));

        if (! $phoneAuth->isValidPhone($phone)) {
            $telegram->sendMessage($chatId, 'Потрібен український номер у форматі +380XXXXXXXXX.');
            $this->requestContact($telegram, $chatId);

            return;
        }

        if ($senderId && Schema::hasTable('telegram_auth_contacts')) {
            TelegramAuthContact::updateOrCreate(
                ['telegram_user_id' => (string) $senderId],
                [
                    'phone' => $phone,
                    'first_name' => data_get($message, 'from.first_name') ?: data_get($contact, 'first_name'),
                    'username' => data_get($message, 'from.username'),
                ]
            );
        }

        $this->issueCodeForPhone($phone, $phoneAuth, $telegram, $chatId);
    }

    private function requestContact(TelegramBotService $telegram, int|string $chatId): void
    {
        $telegram->sendMessage(
            $chatId,
            'Натисніть кнопку нижче, щоб поділитися своїм номером телефону. Після цього я надішлю код FileProxy.',
            [
                'keyboard' => [
                    [
                        [
                            'text' => 'Поділитися контактом',
                            'request_contact' => true,
                        ],
                    ],
                ],
                'resize_keyboard' => true,
                'one_time_keyboard' => true,
            ]
        );
    }

    private function sendCode(TelegramBotService $telegram, int|string $chatId, string $code, ?string $phone = null): void
    {
        $phoneLine = $phone ? " для номера {$phone}" : '';

        $telegram->sendMessage(
            $chatId,
            "Ваш код FileProxy{$phoneLine}: {$code}\nКод діє 10 хвилин.",
            ['remove_keyboard' => true]
        );
    }

    private function issueCodeForPhone(
        string $phone,
        PhoneAuthService $phoneAuth,
        TelegramBotService $telegram,
        int|string $chatId
    ): void {
        $phone = $phoneAuth->normalizePhone($phone);

        if (! $phoneAuth->isValidPhone($phone)) {
            $telegram->sendMessage($chatId, 'Не вдалося визначити номер телефону. Поділіться контактом ще раз.');
            $this->requestContact($telegram, $chatId);

            return;
        }

        $code = $phoneAuth->issueCodeForPhone($phone);

        if (! $code) {
            $telegram->sendMessage($chatId, "Код не створено для номера {$phone}. Спробуйте ще раз або поверніться у форму FileProxy.");

            return;
        }

        $this->sendCode($telegram, $chatId, $code, $phone);
    }

    private function contactPhoneNumber(array $contact): string
    {
        $phone = trim((string) data_get($contact, 'phone_number', ''));

        if ($phone !== '') {
            return $phone;
        }

        $vcard = (string) data_get($contact, 'vcard', '');

        if ($vcard !== '' && preg_match('/TEL[^:]*:([^\r\n]+)/i', $vcard, $matches)) {
            return trim($matches[1]);
        }

        return '';
    }
}
