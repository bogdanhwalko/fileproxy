<?php

namespace Tests\Feature;

use App\Models\FileFolder;
use App\Models\ManagedFile;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ShareLinksTest extends TestCase
{
    use DatabaseTransactions;

    public function test_owner_can_create_and_revoke_public_file_link(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('uploads/readme.txt', "Hello\nFileProxy");

        $user = User::factory()->create();
        $file = ManagedFile::create([
            'user_id' => $user->id,
            'storage_driver' => 'local',
            'original_name' => 'readme.txt',
            'stored_name' => 'readme.txt',
            'path' => 'uploads/readme.txt',
            'mime_type' => 'text/plain',
            'extension' => 'txt',
            'size' => 15,
        ]);

        $this->actingAs($user)
            ->post(route('files.share', $file))
            ->assertRedirect();

        $file->refresh();

        $this->assertNotNull($file->share_token);

        auth()->logout();

        $this->get(route('share.files.show', $file->share_token))
            ->assertOk()
            ->assertSee('readme.txt')
            ->assertSee('Hello')
            ->assertSee('FileProxy');

        $this->get(route('share.files.download', $file->share_token))
            ->assertOk()
            ->assertHeader('content-type', 'text/plain; charset=UTF-8');

        $token = $file->share_token;

        $this->actingAs($user)
            ->delete(route('files.share.destroy', $file))
            ->assertRedirect();

        $this->assertNull($file->refresh()->share_token);

        auth()->logout();

        $this->get(route('share.files.show', $token))
            ->assertNotFound();
    }

    public function test_file_share_link_can_be_created_and_configured_without_page_reload(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('uploads/readme.txt', "Hello\nFileProxy");

        $user = User::factory()->create();
        $file = ManagedFile::create([
            'user_id' => $user->id,
            'storage_driver' => 'local',
            'original_name' => 'readme.txt',
            'stored_name' => 'readme.txt',
            'path' => 'uploads/readme.txt',
            'mime_type' => 'text/plain',
            'extension' => 'txt',
            'size' => 15,
        ]);

        $createResponse = $this->actingAs($user)
            ->postJson(route('files.share', $file));

        $createResponse
            ->assertOk()
            ->assertJsonPath('share.is_enabled', true)
            ->assertJsonStructure(['share' => ['token', 'url', 'usage_label']]);

        $file->refresh();

        $expiresAt = now()->addDay()->format('Y-m-d\TH:i');

        $this->patchJson(route('files.share.update', $file), [
            'share_max_views' => 2,
            'share_expires_at' => $expiresAt,
        ])
            ->assertOk()
            ->assertJsonPath('share.share_max_views', 2);

        $file->refresh();

        $this->assertSame(2, $file->share_max_views);
        $this->assertNotNull($file->share_expires_at);

        $this->deleteJson(route('files.share.destroy', $file))
            ->assertOk()
            ->assertJsonPath('share.is_enabled', false);

        $this->assertNull($file->refresh()->share_token);
        $this->assertSame(0, $file->share_views_count);
        $this->assertNull($file->share_max_views);
        $this->assertNull($file->share_expires_at);
    }

    public function test_folder_share_link_can_be_created_and_configured_without_page_reload(): void
    {
        $user = User::factory()->create();
        $folder = $user->folders()->create(['name' => 'Documents']);

        $createResponse = $this->actingAs($user)
            ->postJson(route('folders.share', $folder));

        $createResponse
            ->assertOk()
            ->assertJsonPath('share.is_enabled', true)
            ->assertJsonStructure(['share' => ['token', 'url', 'usage_label']]);

        $folder->refresh();

        $expiresAt = now()->addDay()->format('Y-m-d\TH:i');

        $this->patchJson(route('folders.share.update', $folder), [
            'share_max_views' => 3,
            'share_expires_at' => $expiresAt,
        ])
            ->assertOk()
            ->assertJsonPath('share.share_max_views', 3);

        $folder->refresh();

        $this->assertSame(3, $folder->share_max_views);
        $this->assertNotNull($folder->share_expires_at);

        $this->deleteJson(route('folders.share.destroy', $folder))
            ->assertOk()
            ->assertJsonPath('share.is_enabled', false);

        $this->assertNull($folder->refresh()->share_token);
        $this->assertSame(0, $folder->share_views_count);
        $this->assertNull($folder->share_max_views);
        $this->assertNull($folder->share_expires_at);
    }

    public function test_public_file_link_respects_view_limit(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('uploads/readme.txt', 'Limited');

        $user = User::factory()->create();
        $file = ManagedFile::create([
            'user_id' => $user->id,
            'storage_driver' => 'local',
            'original_name' => 'readme.txt',
            'stored_name' => 'readme.txt',
            'path' => 'uploads/readme.txt',
            'mime_type' => 'text/plain',
            'extension' => 'txt',
            'size' => 7,
            'share_token' => 'limited-token',
            'share_max_views' => 2,
        ]);

        $this->get(route('share.files.show', 'limited-token'))
            ->assertOk()
            ->assertSee('Limited');

        $this->get(route('share.files.show', 'limited-token'))
            ->assertOk()
            ->assertSee('Limited');

        $this->assertSame(2, $file->refresh()->share_views_count);

        $this->get(route('share.files.show', 'limited-token'))
            ->assertNotFound();
    }

    public function test_public_file_link_respects_expiration_date(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('uploads/readme.txt', 'Expired');

        $user = User::factory()->create();

        ManagedFile::create([
            'user_id' => $user->id,
            'storage_driver' => 'local',
            'original_name' => 'readme.txt',
            'stored_name' => 'readme.txt',
            'path' => 'uploads/readme.txt',
            'mime_type' => 'text/plain',
            'extension' => 'txt',
            'size' => 7,
            'share_token' => 'expired-token',
            'share_expires_at' => now()->subMinute(),
        ]);

        $this->get(route('share.files.show', 'expired-token'))
            ->assertNotFound();
    }

    public function test_public_folder_link_respects_view_limit(): void
    {
        $user = User::factory()->create();
        $folder = $user->folders()->create([
            'name' => 'Limited',
            'share_token' => 'limited-folder-token',
            'share_max_views' => 2,
        ]);

        $this->get(route('share.folders.show', 'limited-folder-token'))
            ->assertOk()
            ->assertSee('Limited');

        $this->get(route('share.folders.show', 'limited-folder-token'))
            ->assertOk()
            ->assertSee('Limited');

        $this->assertSame(2, $folder->refresh()->share_views_count);

        $this->get(route('share.folders.show', 'limited-folder-token'))
            ->assertNotFound();
    }

    public function test_public_folder_link_respects_expiration_date(): void
    {
        $user = User::factory()->create();

        $user->folders()->create([
            'name' => 'Expired',
            'share_token' => 'expired-folder-token',
            'share_expires_at' => now()->subMinute(),
        ]);

        $this->get(route('share.folders.show', 'expired-folder-token'))
            ->assertNotFound();
    }

    public function test_other_user_cannot_share_foreign_file_or_folder(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $folder = $owner->folders()->create(['name' => 'Private']);
        $file = ManagedFile::create([
            'user_id' => $owner->id,
            'folder_id' => $folder->id,
            'storage_driver' => 'local',
            'original_name' => 'secret.txt',
            'stored_name' => 'secret.txt',
            'path' => 'uploads/secret.txt',
            'mime_type' => 'text/plain',
            'extension' => 'txt',
            'size' => 10,
        ]);

        $this->actingAs($intruder)
            ->post(route('files.share', $file))
            ->assertNotFound();

        $this->actingAs($intruder)
            ->post(route('folders.share', $folder))
            ->assertNotFound();

        $this->assertNull($file->refresh()->share_token);
        $this->assertNull($folder->refresh()->share_token);
    }

    public function test_shared_folder_allows_public_file_preview_and_download_archive(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('uploads/folder/notes.txt', "Folder\nNotes");
        Storage::disk('local')->put('uploads/folder/report.pdf', 'PDF');

        $user = User::factory()->create();
        $folder = FileFolder::create([
            'user_id' => $user->id,
            'name' => 'Documents',
        ]);
        $notes = ManagedFile::create([
            'user_id' => $user->id,
            'folder_id' => $folder->id,
            'storage_driver' => 'local',
            'original_name' => 'notes.txt',
            'stored_name' => 'notes.txt',
            'path' => 'uploads/folder/notes.txt',
            'mime_type' => 'text/plain',
            'extension' => 'txt',
            'size' => 12,
        ]);
        $report = ManagedFile::create([
            'user_id' => $user->id,
            'folder_id' => $folder->id,
            'storage_driver' => 'local',
            'original_name' => 'report.pdf',
            'stored_name' => 'report.pdf',
            'path' => 'uploads/folder/report.pdf',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'size' => 3,
        ]);

        $this->actingAs($user)
            ->post(route('folders.share', $folder))
            ->assertRedirect();

        $folder->refresh();

        auth()->logout();

        $this->get(route('share.folders.show', $folder->share_token))
            ->assertOk()
            ->assertSee('Documents')
            ->assertSee('notes.txt')
            ->assertSee('report.pdf')
            ->assertSee(route('share.folders.files.download', [$folder->share_token, $report]), false);

        $this->get(route('share.folders.files.show', [$folder->share_token, $notes]))
            ->assertOk()
            ->assertSee('Folder')
            ->assertSee('Notes');

        $this->get(route('share.folders.download', $folder->share_token))
            ->assertOk()
            ->assertHeader('content-type', 'application/zip');
    }

    public function test_owner_cannot_publicly_share_a_password_protected_folder(): void
    {
        $user = User::factory()->create();
        $folder = $user->folders()->create(['name' => 'Vault']);
        $folder->setFolderPassword('super-secret');
        $folder->save();

        $this->actingAs($user)
            ->postJson(route('folders.share', $folder))
            ->assertStatus(422);

        $this->assertNull($folder->refresh()->share_token);
    }

    public function test_public_folder_link_stops_working_if_folder_becomes_password_protected(): void
    {
        // Defensive backstop: even if a folder somehow ends up with both a
        // share_token AND a password (e.g. data imported/edited outside the
        // normal shareFolder() guard), the public route must still refuse it.
        $user = User::factory()->create();
        $folder = $user->folders()->create([
            'name' => 'Vault',
            'share_token' => 'vault-token',
        ]);
        $folder->setFolderPassword('super-secret');
        $folder->save();

        $this->get(route('share.folders.show', 'vault-token'))
            ->assertNotFound();

        $this->get(route('share.folders.download', 'vault-token'))
            ->assertNotFound();
    }

    public function test_shared_folder_link_rejects_files_from_other_folders(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('uploads/one.txt', 'one');
        Storage::disk('local')->put('uploads/two.txt', 'two');

        $user = User::factory()->create();
        $sharedFolder = $user->folders()->create([
            'name' => 'Shared',
            'share_token' => 'shared-token',
        ]);
        $privateFolder = $user->folders()->create(['name' => 'Private']);
        $outsideFile = ManagedFile::create([
            'user_id' => $user->id,
            'folder_id' => $privateFolder->id,
            'storage_driver' => 'local',
            'original_name' => 'two.txt',
            'stored_name' => 'two.txt',
            'path' => 'uploads/two.txt',
            'mime_type' => 'text/plain',
            'extension' => 'txt',
            'size' => 3,
        ]);

        ManagedFile::create([
            'user_id' => $user->id,
            'folder_id' => $sharedFolder->id,
            'storage_driver' => 'local',
            'original_name' => 'one.txt',
            'stored_name' => 'one.txt',
            'path' => 'uploads/one.txt',
            'mime_type' => 'text/plain',
            'extension' => 'txt',
            'size' => 3,
        ]);

        $this->get(route('share.folders.files.download', ['shared-token', $outsideFile]))
            ->assertNotFound();
    }
}
