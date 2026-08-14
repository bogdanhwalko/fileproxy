<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\PhoneAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Phone + Telegram-bot login for API clients (e.g. the mobile app) — mints a
 * Sanctum personal access token, mirroring the web session login in
 * App\Http\Controllers\Auth\AuthenticatedSessionController but returning a
 * bearer token instead of a cookie session. Unauthenticated by design (these
 * routes exist to obtain a token in the first place); throttled the same way
 * as the web login form.
 */
class AuthController extends Controller
{
    /**
     * Step 1: start a challenge for a phone number and return the Telegram
     * deep link. Anti-enumeration: succeeds the same way whether or not the
     * phone belongs to a registered account — ownership is only checked at
     * verify() time.
     */
    public function login(Request $request, PhoneAuthService $phoneAuth): JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:32'],
        ]);

        $phone = $phoneAuth->normalizePhone($validated['phone']);
        $this->validatePhone($phone);

        $challenge = $phoneAuth->createChallenge($phone);

        return response()->json([
            'challenge_token' => $challenge->token,
            'bot_link' => $phoneAuth->telegramLink($challenge),
            'expires_in' => 600,
            // Only set outside production when services.telegram.show_code_locally
            // is enabled — lets local/dev clients skip the real Telegram round-trip.
            'local_code' => $phoneAuth->localCodeForChallenge($challenge),
        ]);
    }

    /**
     * Step 2: exchange the phone + 6-digit Telegram code for a bearer token.
     */
    public function verify(Request $request, PhoneAuthService $phoneAuth): JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:32'],
            'code' => ['required', 'digits:6'],
            'challenge_token' => ['nullable', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $phone = $phoneAuth->normalizePhone($validated['phone']);
        $this->validatePhone($phone);

        $codeValid = $phoneAuth->verify(
            (string) ($validated['challenge_token'] ?? ''),
            $phone,
            $validated['code']
        );

        $user = User::where('phone', $phone)->first();

        // Same generic message regardless of which check failed — never reveal
        // whether a phone number has an account (matches the web login).
        if (! $codeValid || ! $user || $user->is_blocked) {
            return response()->json([
                'message' => 'Invalid phone number, code, or account unavailable.',
            ], 422);
        }

        if ($user->tokens()->count() >= User::API_TOKEN_LIMIT) {
            return response()->json([
                'message' => 'Token limit of '.User::API_TOKEN_LIMIT.' reached. Revoke an old token first.',
            ], 403);
        }

        $deviceName = trim((string) ($validated['device_name'] ?? '')) ?: 'mobile-app';
        $token = $user->createToken($deviceName);

        return response()->json([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'phone' => $user->phone,
                'is_admin' => (bool) $user->is_admin,
            ],
        ], 201);
    }

    private function validatePhone(string $phone): void
    {
        Validator::make(['phone' => $phone], [
            'phone' => ['required', 'regex:/^\+[1-9][0-9]{7,14}$/'],
        ], [
            'phone.regex' => 'Provide the phone number in international format, e.g. +380671234567.',
        ])->validate();
    }
}
