<?php

namespace App\Http\Controllers;

use App\Models\TelegramBotToken;
use App\Services\TelegramStorageBotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramStorageWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        TelegramBotToken $bot,
        string $secret,
        TelegramStorageBotService $telegram
    ): JsonResponse {
        abort_unless($bot->webhook_secret && hash_equals((string) $bot->webhook_secret, $secret), 404);

        $update = $request->all();

        Log::info('FileProxy: storage webhook received', [
            'bot_id' => $bot->id,
            'has_my_chat_member' => isset($update['my_chat_member']),
            'has_message' => isset($update['message']),
            'update_keys' => array_keys($update),
        ]);

        $result = $telegram->tryRegisterFromUpdate($bot, $update, true);

        Log::info('FileProxy: storage webhook processed', [
            'bot_id' => $bot->id,
            'handled' => $result['handled'],
            'created' => $result['created'],
            'reason' => $result['reason'],
        ]);

        return response()->json(['ok' => true]);
    }
}
