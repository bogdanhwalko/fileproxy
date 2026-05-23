<?php

namespace App\Services;

use App\Models\FilePurchase;
use App\Models\ManagedFile;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class LemonSqueezyService
{
    private const API_BASE = 'https://api.lemonsqueezy.com/v1';

    public function isConfigured(): bool
    {
        $config = config('services.lemonsqueezy');

        return ! empty($config['api_key'])
            && ! empty($config['store_id'])
            && ! empty($config['variant_id'])
            && ! empty($config['signing_secret']);
    }

    public function createCheckout(ManagedFile $file, FilePurchase $purchase, string $redirectUrl): string
    {
        $config = config('services.lemonsqueezy');

        $payload = [
            'data' => [
                'type' => 'checkouts',
                'attributes' => [
                    'checkout_data' => [
                        'custom' => [
                            'purchase_id' => (string) $purchase->id,
                            'access_token' => $purchase->access_token,
                        ],
                    ],
                    'product_options' => [
                        'name' => 'File access: '.$file->original_name,
                        'description' => 'One-time access to the file "'.$file->original_name.'" via FileProxy.',
                        'redirect_url' => $redirectUrl,
                        'receipt_button_text' => 'Open file',
                        'receipt_link_url' => $redirectUrl,
                        'enabled_variants' => [(int) $config['variant_id']],
                    ],
                    'checkout_options' => [
                        'embed' => false,
                        'media' => false,
                        'logo' => true,
                    ],
                    'custom_price' => (int) $file->price_cents,
                    'preview' => false,
                    'test_mode' => (bool) $config['test_mode'],
                ],
                'relationships' => [
                    'store' => [
                        'data' => [
                            'type' => 'stores',
                            'id' => (string) $config['store_id'],
                        ],
                    ],
                    'variant' => [
                        'data' => [
                            'type' => 'variants',
                            'id' => (string) $config['variant_id'],
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->client()->post('/checkouts', $payload);

        if (! $response->successful()) {
            throw new RuntimeException('Lemon Squeezy checkout failed: '.$response->status().' '.$response->body());
        }

        $url = $response->json('data.attributes.url');
        $checkoutId = $response->json('data.id');

        if (! $url) {
            throw new RuntimeException('Lemon Squeezy did not return a checkout URL.');
        }

        if ($checkoutId) {
            $purchase->forceFill(['lemon_checkout_id' => $checkoutId])->save();
        }

        return $url;
    }

    public function verifySignature(string $rawBody, ?string $signatureHeader): bool
    {
        $secret = config('services.lemonsqueezy.signing_secret');

        if (! $secret || ! $signatureHeader) {
            return false;
        }

        $expected = hash_hmac('sha256', $rawBody, $secret);

        return hash_equals($expected, $signatureHeader);
    }

    private function client(): PendingRequest
    {
        $apiKey = config('services.lemonsqueezy.api_key');

        return Http::baseUrl(self::API_BASE)
            ->timeout(15)
            ->withHeaders([
                'Accept' => 'application/vnd.api+json',
                'Content-Type' => 'application/vnd.api+json',
                'Authorization' => 'Bearer '.$apiKey,
            ]);
    }
}
