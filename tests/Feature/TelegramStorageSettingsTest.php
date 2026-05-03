<?php

namespace Tests\Feature;

use App\Models\TelegramBotToken;
use App\Models\TelegramStorageGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class TelegramStorageSettingsTest extends TestCase
{
    use DatabaseTransactions;

    public function test_user_can_add_bot_token_and_storage_group(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('telegram-settings.bots.store'), [
                'name' => 'Storage Bot',
                'username' => '@storage_bot',
                'token' => '123456:ABCDEF',
            ])
            ->assertRedirect(route('telegram-settings.index'));

        $bot = TelegramBotToken::where('user_id', $user->id)->firstOrFail();

        $this->assertSame('Storage Bot', $bot->name);
        $this->assertSame('@storage_bot', $bot->username);
        $this->assertSame('123456:ABCDEF', $bot->token);

        $this->actingAs($user)
            ->post(route('telegram-settings.groups.store'), [
                'telegram_bot_token_id' => $bot->id,
                'title' => 'Archive',
                'chat_id' => '-1001234567890',
            ])
            ->assertRedirect(route('telegram-settings.index'));

        $this->assertDatabaseHas('telegram_storage_groups', [
            'user_id' => $user->id,
            'telegram_bot_token_id' => $bot->id,
            'title' => 'Archive',
            'chat_id' => '-1001234567890',
        ]);

        $this->actingAs($user)
            ->get(route('telegram-settings.index'))
            ->assertOk()
            ->assertSee('Storage Bot')
            ->assertSee('Archive');
    }

    public function test_user_cannot_use_another_users_bot_for_group(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $bot = TelegramBotToken::create([
            'user_id' => $owner->id,
            'name' => 'Private Bot',
            'token' => '123456:ABCDEF',
        ]);

        $this->actingAs($other)
            ->post(route('telegram-settings.groups.store'), [
                'telegram_bot_token_id' => $bot->id,
                'title' => 'Archive',
                'chat_id' => '-1001234567890',
            ])
            ->assertSessionHasErrors('telegram_bot_token_id');

        $this->assertDatabaseMissing('telegram_storage_groups', [
            'user_id' => $other->id,
            'telegram_bot_token_id' => $bot->id,
        ]);
    }

    public function test_user_can_change_default_bot_and_group(): void
    {
        $user = User::factory()->create();
        $firstBot = TelegramBotToken::create([
            'user_id' => $user->id,
            'name' => 'First Bot',
            'token' => '111:AAA',
            'is_default' => true,
        ]);
        $secondBot = TelegramBotToken::create([
            'user_id' => $user->id,
            'name' => 'Second Bot',
            'token' => '222:BBB',
            'is_default' => false,
        ]);
        $firstGroup = TelegramStorageGroup::create([
            'user_id' => $user->id,
            'telegram_bot_token_id' => $firstBot->id,
            'title' => 'First Group',
            'chat_id' => '-100111',
            'is_default' => true,
        ]);
        $secondGroup = TelegramStorageGroup::create([
            'user_id' => $user->id,
            'telegram_bot_token_id' => $secondBot->id,
            'title' => 'Second Group',
            'chat_id' => '-100222',
            'is_default' => false,
        ]);

        $this->actingAs($user)
            ->post(route('telegram-settings.bots.default', $secondBot))
            ->assertRedirect(route('telegram-settings.index'));

        $this->assertFalse($firstBot->fresh()->is_default);
        $this->assertTrue($secondBot->fresh()->is_default);

        $this->actingAs($user)
            ->post(route('telegram-settings.groups.default', $secondGroup))
            ->assertRedirect(route('telegram-settings.index'));

        $this->assertFalse($firstGroup->fresh()->is_default);
        $this->assertTrue($secondGroup->fresh()->is_default);
    }

    public function test_admin_can_mark_own_group_as_system_default(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $bot = TelegramBotToken::create([
            'user_id' => $admin->id,
            'name' => 'System Bot',
            'token' => '123456:ABCDEF',
        ]);
        $group = TelegramStorageGroup::create([
            'user_id' => $admin->id,
            'telegram_bot_token_id' => $bot->id,
            'title' => 'System Archive',
            'chat_id' => '-1001234567890',
            'is_global_default' => false,
        ]);

        $this->actingAs($admin)
            ->post(route('telegram-settings.groups.global-default', $group))
            ->assertRedirect(route('telegram-settings.index'));

        $this->assertTrue($group->fresh()->is_global_default);

        $this->actingAs($admin)
            ->delete(route('telegram-settings.groups.global-default.remove', $group))
            ->assertRedirect(route('telegram-settings.index'));

        $this->assertFalse($group->fresh()->is_global_default);
    }

    public function test_regular_user_cannot_mark_group_as_system_default(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $bot = TelegramBotToken::create([
            'user_id' => $user->id,
            'name' => 'Storage Bot',
            'token' => '123456:ABCDEF',
        ]);
        $group = TelegramStorageGroup::create([
            'user_id' => $user->id,
            'telegram_bot_token_id' => $bot->id,
            'title' => 'Archive',
            'chat_id' => '-1001234567890',
            'is_global_default' => false,
        ]);

        $this->actingAs($user)
            ->post(route('telegram-settings.groups.global-default', $group))
            ->assertForbidden();

        $this->assertFalse($group->fresh()->is_global_default);
    }

    public function test_user_can_open_telegram_binding_instruction_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('telegram.setup'))
            ->assertOk()
            ->assertSee('Прив’язка Telegram')
            ->assertSee('Налаштувати Telegram-сховище');
    }

    public function test_user_can_delete_storage_group(): void
    {
        $user = User::factory()->create();
        $bot = TelegramBotToken::create([
            'user_id' => $user->id,
            'name' => 'Storage Bot',
            'token' => '123456:ABCDEF',
        ]);
        $group = TelegramStorageGroup::create([
            'user_id' => $user->id,
            'telegram_bot_token_id' => $bot->id,
            'title' => 'Archive',
            'chat_id' => '-1001234567890',
        ]);

        $this->actingAs($user)
            ->delete(route('telegram-settings.groups.destroy', $group))
            ->assertRedirect(route('telegram-settings.index'));

        $this->assertDatabaseMissing('telegram_storage_groups', ['id' => $group->id]);
    }
}
