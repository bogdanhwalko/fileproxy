<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\PhoneAuthService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.telegram.show_code_locally' => false]);
    }

    public function test_guest_can_view_home_page(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('FileProxy')
            ->assertSee('безкоштовно')
            ->assertDontSee('Мої файли')
            ->assertDontSee('Docker')
            ->assertDontSee('Laravel');
    }

    public function test_guest_is_redirected_from_files_to_login(): void
    {
        $this->get(route('files.index'))
            ->assertRedirect(route('login'));
    }

    public function test_register_starts_telegram_code_challenge(): void
    {
        $this->post(route('register.store'), [
            'nickname' => 'Demo User',
            'phone' => $this->uniquePhone(),
        ])
            ->assertRedirect()
            ->assertSessionHas('telegram_auth');
    }

    public function test_user_can_register_with_telegram_code(): void
    {
        $phoneAuth = app(PhoneAuthService::class);
        $phone = $this->uniquePhone();
        $challenge = $phoneAuth->createChallenge($phone);
        $code = $phoneAuth->generateCodeForToken($challenge->token);

        $response = $this->post(route('register.store'), [
            'nickname' => 'Demo User',
            'phone' => $phone,
            'challenge_token' => $challenge->token,
            'telegram_code' => $code,
        ]);

        $response->assertRedirect(route('files.index'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'name' => 'Demo User',
            'phone' => $phone,
        ]);
    }

    public function test_register_can_show_local_code_when_enabled(): void
    {
        config(['services.telegram.show_code_locally' => true]);

        $this->post(route('register.store'), [
            'nickname' => 'Demo User',
            'phone' => $this->uniquePhone(),
        ])
            ->assertRedirect()
            ->assertSessionHas('telegram_auth', function (array $auth): bool {
                return isset($auth['local_code'])
                    && preg_match('/^[0-9]{6}$/', $auth['local_code']) === 1;
            });
    }

    public function test_login_starts_telegram_code_challenge_for_existing_user(): void
    {
        $phone = $this->uniquePhone();
        User::factory()->create([
            'phone' => $phone,
        ]);

        $this->post(route('login.store'), [
            'phone' => $phone,
        ])
            ->assertRedirect()
            ->assertSessionHas('telegram_auth');
    }

    public function test_blocked_user_cannot_start_login(): void
    {
        $phone = $this->uniquePhone();
        User::factory()->create([
            'phone' => $phone,
            'is_blocked' => true,
        ]);

        $this->post(route('login.store'), [
            'phone' => $phone,
        ])
            ->assertSessionHasErrors('phone');

        $this->assertGuest();
    }

    public function test_user_can_login_and_logout_with_telegram_code(): void
    {
        $phoneAuth = app(PhoneAuthService::class);
        $phone = $this->uniquePhone();
        $user = User::factory()->create([
            'phone' => $phone,
        ]);
        $challenge = $phoneAuth->createChallenge($phone);
        $code = $phoneAuth->generateCodeForToken($challenge->token);

        $this->post(route('login.store'), [
            'phone' => $phone,
            'challenge_token' => $challenge->token,
            'telegram_code' => $code,
        ])->assertRedirect(route('files.index'));

        $this->assertAuthenticatedAs($user);

        $this->post(route('logout'))
            ->assertRedirect(route('home'));

        $this->assertFalse(Auth::check());
    }

    private function uniquePhone(): string
    {
        return '+38050'.random_int(1000000, 9999999);
    }
}
