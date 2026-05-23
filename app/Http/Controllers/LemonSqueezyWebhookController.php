<?php

namespace App\Http\Controllers;

use App\Models\FilePurchase;
use App\Services\LemonSqueezyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class LemonSqueezyWebhookController extends Controller
{
    public function __invoke(Request $request, LemonSqueezyService $lemon): JsonResponse
    {
        $rawBody = $request->getContent();
        $signature = $request->header('X-Signature');

        if (! $lemon->verifySignature($rawBody, $signature)) {
            Log::warning('Lemon Squeezy webhook: invalid signature', [
                'ip' => $request->ip(),
            ]);

            return response()->json(['error' => 'invalid_signature'], 401);
        }

        $payload = $request->json()->all();
        $eventName = $payload['meta']['event_name'] ?? null;
        $custom = $payload['meta']['custom_data'] ?? [];
        $purchaseId = $custom['purchase_id'] ?? null;

        if (! $purchaseId) {
            return response()->json(['status' => 'ignored', 'reason' => 'no_purchase_id']);
        }

        $purchase = FilePurchase::query()->find($purchaseId);

        if (! $purchase) {
            Log::warning('Lemon Squeezy webhook: purchase not found', ['purchase_id' => $purchaseId]);

            return response()->json(['status' => 'ignored', 'reason' => 'purchase_missing']);
        }

        match ($eventName) {
            'order_created' => $this->handleOrderCreated($purchase, $payload),
            'order_refunded' => $this->handleOrderRefunded($purchase),
            default => null,
        };

        return response()->json(['status' => 'ok']);
    }

    private function handleOrderCreated(FilePurchase $purchase, array $payload): void
    {
        $attributes = $payload['data']['attributes'] ?? [];
        $orderId = $payload['data']['id'] ?? null;
        $email = $attributes['user_email'] ?? null;
        $totalCents = $attributes['total'] ?? null;
        $currency = $attributes['currency'] ?? null;
        $status = $attributes['status'] ?? null;

        if ($status === 'refunded') {
            $this->handleOrderRefunded($purchase);

            return;
        }

        if ($purchase->is_paid) {
            return;
        }

        $expiresAt = null;
        $accessHours = $purchase->file?->purchase_access_hours;

        if ($accessHours) {
            $expiresAt = now()->addHours($accessHours);
        }

        $purchase->forceFill([
            'status' => FilePurchase::STATUS_PAID,
            'lemon_order_id' => $orderId,
            'buyer_email' => $email,
            'amount_cents' => is_numeric($totalCents) ? (int) $totalCents : $purchase->amount_cents,
            'currency' => $currency ?: $purchase->currency,
            'paid_at' => Carbon::now(),
            'expires_at' => $expiresAt,
        ])->save();
    }

    private function handleOrderRefunded(FilePurchase $purchase): void
    {
        $purchase->forceFill([
            'status' => FilePurchase::STATUS_REFUNDED,
        ])->save();
    }
}
