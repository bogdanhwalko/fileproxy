<?php

namespace Tests\Feature;

use App\Models\FileFolder;
use App\Models\ManagedFile;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The folder password is a session-scoped gate meant to protect a folder's
 * contents even from the owner's own authenticated session (e.g. a stolen
 * cookie or an unattended, still-logged-in browser). These tests cover that
 * every route capable of reaching a locked folder's file directly by ID
 * actually enforces the gate, not just the folder's own dedicated view.
 */
class FolderPasswordAccessTest extends TestCase
{
    use DatabaseTransactions;

    private function lockedFolderWithFile(): array
    {
        Storage::fake('local');
        Storage::disk('local')->put('uploads/vault/note.txt', 'Secret note');

        $user = User::factory()->create();
        $folder = $user->folders()->create(['name' => 'Vault']);
        $folder->setFolderPassword('super-secret');
        $folder->save();

        $file = ManagedFile::create([
            'user_id' => $user->id,
            'folder_id' => $folder->id,
            'storage_driver' => 'local',
            'original_name' => 'note.txt',
            'stored_name' => 'note.txt',
            'path' => 'uploads/vault/note.txt',
            'mime_type' => 'text/plain',
            'extension' => 'txt',
            'size' => 11,
        ]);

        return [$user, $folder, $file];
    }

    public function test_owner_cannot_download_file_from_locked_folder_by_direct_id(): void
    {
        [$user, , $file] = $this->lockedFolderWithFile();

        $this->actingAs($user)
            ->get(route('files.download', $file))
            ->assertNotFound();
    }

    public function test_owner_cannot_preview_file_from_locked_folder_by_direct_id(): void
    {
        [$user, , $file] = $this->lockedFolderWithFile();

        $this->actingAs($user)
            ->get(route('files.preview', $file))
            ->assertNotFound();
    }

    public function test_owner_cannot_inline_view_image_from_locked_folder_by_direct_id(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('uploads/vault/photo.png', 'fake-png-bytes');

        $user = User::factory()->create();
        $folder = $user->folders()->create(['name' => 'Vault']);
        $folder->setFolderPassword('super-secret');
        $folder->save();

        $file = ManagedFile::create([
            'user_id' => $user->id,
            'folder_id' => $folder->id,
            'storage_driver' => 'local',
            'original_name' => 'photo.png',
            'stored_name' => 'photo.png',
            'path' => 'uploads/vault/photo.png',
            'mime_type' => 'image/png',
            'extension' => 'png',
            'size' => 14,
        ]);

        $this->actingAs($user)
            ->get(route('files.inline', $file))
            ->assertNotFound();
    }

    public function test_owner_cannot_download_archive_of_locked_folder_by_id(): void
    {
        [$user, $folder] = $this->lockedFolderWithFile();

        $this->actingAs($user)
            ->get(route('files.archive', ['folder' => $folder->id]))
            ->assertNotFound();
    }

    public function test_owner_can_access_file_in_folder_after_unlocking_it(): void
    {
        [$user, $folder, $file] = $this->lockedFolderWithFile();

        $this->actingAs($user)
            ->post(route('folders.unlock', $folder), ['password' => 'super-secret'])
            ->assertRedirect();

        $this->actingAs($user)
            ->get(route('files.download', $file))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('files.archive', ['folder' => $folder->id]))
            ->assertOk()
            ->assertHeader('content-type', 'application/zip');
    }

    public function test_unrelated_user_still_gets_404_regardless_of_lock_state(): void
    {
        [, , $file] = $this->lockedFolderWithFile();
        $intruder = User::factory()->create();

        $this->actingAs($intruder)
            ->get(route('files.download', $file))
            ->assertNotFound();
    }
}
