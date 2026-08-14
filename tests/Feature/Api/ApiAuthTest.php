<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Services\PhoneAuthService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ApiAuthTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.telegram.show_code_locally' => false]);
    }

    public function test_login_issues_a_challenge_without_revealing_whether_the_account_exists(): void
    {
        $phone = $this->uniquePhone();

        $response = $this->postJson(route('api.v1.auth.login'), ['phone' => $phone]);

        $response->assertOk()
            ->assertJsonStructure(['challenge_token', 'bot_link', 'expires_in', 'local_code']);

        // No account exists for this phone yet — response shape must be
        // identical to the "account exists" case (anti-enumeration).
        $user = User::factory()->create(['phone' => $phone]);
        $response2 = $this->postJson(route('api.v1.auth.login'), ['phone' => $phone]);

        $response2->assertOk()->assertJsonStructure(['challenge_token', 'bot_link', 'expires_in', 'local_code']);
    }

    public function test_login_rejects_malformed_phone(): void
    {
        $this->postJson(route('api.v1.auth.login'), ['phone' => 'not-a-phone'])
            ->assertStatus(422);
    }

    public function test_verify_issues_a_working_bearer_token_for_a_valid_code(): void
    {
        $phoneAuth = app(PhoneAuthService::class);
        $phone = $this->uniquePhone();
        $user = User::factory()->create(['phone' => $phone, 'name' => 'Bohdan']);

        $challenge = $phoneAuth->createChallenge($phone);
        $code = $phoneAuth->generateCodeForToken($challenge->token);

        $response = $this->postJson(route('api.v1.auth.verify'), [
            'phone' => $phone,
            'code' => $code,
            'challenge_token' => $challenge->token,
            'device_name' => 'Test Device',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['token', 'token_type', 'user' => ['id', 'name', 'phone', 'is_admin']])
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('token_type', 'Bearer');

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'Test Device',
        ]);

        // The freshly issued token must actually authenticate subsequent requests.
        $token = $response->json('token');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson(route('api.v1.user'))
            ->assertOk()
            ->assertJsonPath('id', $user->id);
    }

    public function test_verify_rejects_wrong_code_without_revealing_which_part_failed(): void
    {
        $phoneAuth = app(PhoneAuthService::class);
        $phone = $this->uniquePhone();
        User::factory()->create(['phone' => $phone]);

        $challenge = $phoneAuth->createChallenge($phone);
        $phoneAuth->generateCodeForToken($challenge->token);

        $this->postJson(route('api.v1.auth.verify'), [
            'phone' => $phone,
            'code' => '000000',
            'challenge_token' => $challenge->token,
        ])->assertStatus(422);

        $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => User::where('phone', $phone)->value('id')]);
    }

    public function test_verify_rejects_blocked_account_even_with_correct_code(): void
    {
        $phoneAuth = app(PhoneAuthService::class);
        $phone = $this->uniquePhone();
        User::factory()->create(['phone' => $phone, 'is_blocked' => true]);

        $challenge = $phoneAuth->createChallenge($phone);
        $code = $phoneAuth->generateCodeForToken($challenge->token);

        $this->postJson(route('api.v1.auth.verify'), [
            'phone' => $phone,
            'code' => $code,
            'challenge_token' => $challenge->token,
        ])->assertStatus(422);
    }

    public function test_verify_enforces_the_token_limit(): void
    {
        $phoneAuth = app(PhoneAuthService::class);
        $phone = $this->uniquePhone();
        $user = User::factory()->create(['phone' => $phone]);

        for ($i = 0; $i < User::API_TOKEN_LIMIT; $i++) {
            $user->createToken("existing-{$i}");
        }

        $challenge = $phoneAuth->createChallenge($phone);
        $code = $phoneAuth->generateCodeForToken($challenge->token);

        $this->postJson(route('api.v1.auth.verify'), [
            'phone' => $phone,
            'code' => $code,
            'challenge_token' => $challenge->token,
        ])->assertStatus(403);
    }

    private function uniquePhone(): string
    {
        return '+38050'.random_int(1000000, 9999999);
    }
}
