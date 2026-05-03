<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AdminCommandTest extends TestCase
{
    use DatabaseTransactions;

    public function test_command_can_grant_and_remove_admin_rights_by_phone(): void
    {
        $user = User::factory()->create([
            'phone' => '+380501234567',
            'is_admin' => false,
        ]);

        $this->artisan('user:set-admin', ['identifier' => '+380501234567'])
            ->assertSuccessful();

        $this->assertTrue($user->fresh()->is_admin);

        $this->artisan('user:set-admin', [
            'identifier' => '+380501234567',
            '--remove' => true,
        ])
            ->assertSuccessful();

        $this->assertFalse($user->fresh()->is_admin);
    }

    public function test_command_fails_for_unknown_user(): void
    {
        $this->artisan('user:set-admin', ['identifier' => '+380500000000'])
            ->expectsOutput('Користувача не знайдено.')
            ->assertFailed();
    }
}
