<?php

namespace Tests\Feature;

use App\Models\ManagedFile;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_can_view_users_and_their_files(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create([
            'name' => 'Target User',
            'phone' => '+380501234567',
        ]);

        ManagedFile::create([
            'user_id' => $user->id,
            'storage_driver' => 'local',
            'original_name' => 'report.txt',
            'stored_name' => 'report.txt',
            'path' => 'uploads/report.txt',
            'mime_type' => 'text/plain',
            'extension' => 'txt',
            'size' => 20,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('compact-file-table')
            ->assertSee('Target User');

        $this->actingAs($admin)
            ->get(route('admin.users.show', $user))
            ->assertOk()
            ->assertSee('report.txt')
            ->assertSee('Локальне сховище')
            ->assertSee('Папки користувача')
            ->assertSee('Фільтрувати')
            ->assertSee(route('admin.users.files.preview', [$user, ManagedFile::where('original_name', 'report.txt')->firstOrFail()]), false);
    }

    public function test_admin_can_filter_user_files_by_folder_and_view_tiles(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create(['name' => 'Target User']);
        $folder = $user->folders()->create(['name' => 'Reports']);

        ManagedFile::create([
            'user_id' => $user->id,
            'folder_id' => $folder->id,
            'storage_driver' => 'local',
            'original_name' => 'folder-report.txt',
            'stored_name' => 'folder-report.txt',
            'path' => 'uploads/folder-report.txt',
            'mime_type' => 'text/plain',
            'extension' => 'txt',
            'size' => 20,
        ]);
        ManagedFile::create([
            'user_id' => $user->id,
            'storage_driver' => 'local',
            'original_name' => 'root-report.txt',
            'stored_name' => 'root-report.txt',
            'path' => 'uploads/root-report.txt',
            'mime_type' => 'text/plain',
            'extension' => 'txt',
            'size' => 20,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.show', [
                'user' => $user,
                'folder' => $folder->id,
                'view' => 'grid',
            ]))
            ->assertOk()
            ->assertSee('file-grid')
            ->assertSee('Reports')
            ->assertSee('folder-report.txt')
            ->assertDontSee('root-report.txt');
    }

    public function test_admin_can_preview_and_delete_user_file(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('uploads/admin-preview.txt', 'Admin preview text');

        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();
        $file = ManagedFile::create([
            'user_id' => $user->id,
            'storage_driver' => 'local',
            'original_name' => 'admin-preview.txt',
            'stored_name' => 'admin-preview.txt',
            'path' => 'uploads/admin-preview.txt',
            'mime_type' => 'text/plain',
            'extension' => 'txt',
            'size' => 18,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.files.preview', [$user, $file]))
            ->assertOk()
            ->assertSee('Admin preview text');

        $this->actingAs($admin)
            ->delete(route('admin.users.files.destroy', [$user, $file]))
            ->assertRedirect();

        Storage::disk('local')->assertMissing('uploads/admin-preview.txt');
        $this->assertDatabaseMissing('managed_files', ['id' => $file->id]);
    }

    public function test_non_admin_cannot_open_admin_panel(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->get(route('admin.users.index'))
            ->assertForbidden();
    }

    public function test_admin_can_block_and_unblock_user(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create(['is_blocked' => false]);

        $this->actingAs($admin)
            ->post(route('admin.users.block', $user))
            ->assertRedirect();

        $this->assertTrue($user->fresh()->is_blocked);

        $this->actingAs($admin)
            ->post(route('admin.users.unblock', $user))
            ->assertRedirect();

        $this->assertFalse($user->fresh()->is_blocked);
    }

    public function test_blocked_authenticated_user_is_logged_out_from_private_routes(): void
    {
        $user = User::factory()->create(['is_blocked' => true]);

        $this->actingAs($user)
            ->get(route('files.index'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
