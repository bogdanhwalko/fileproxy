<?php

namespace Tests\Feature;

use App\Models\FileFolder;
use App\Models\ManagedFile;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FolderManagementTest extends TestCase
{
    use DatabaseTransactions;

    public function test_folder_can_be_created(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('folders.store'), [
            'name' => 'Contracts',
        ]);

        $folder = FileFolder::where('name', 'Contracts')->firstOrFail();

        $response->assertRedirect(route('files.index', ['folder' => $folder->id]));

        $this->assertDatabaseHas('file_folders', [
            'id' => $folder->id,
            'user_id' => $user->id,
            'name' => 'Contracts',
        ]);
    }

    public function test_file_can_be_uploaded_into_folder(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['is_admin' => true]);
        $folder = $user->folders()->create(['name' => 'Invoices']);

        $response = $this->actingAs($user)->post(route('files.store'), [
            'folder_id' => $folder->id,
            'files' => [
                UploadedFile::fake()->create('invoice.pdf', 64, 'application/pdf'),
            ],
        ]);

        $response->assertRedirect(route('files.index', ['folder' => $folder->id]));

        $file = ManagedFile::where('original_name', 'invoice.pdf')->firstOrFail();

        $this->assertSame($folder->id, $file->folder_id);
        Storage::disk('local')->assertExists($file->path);
    }

    public function test_folder_delete_keeps_files_without_folder(): void
    {
        $user = User::factory()->create();
        $folder = $user->folders()->create(['name' => 'Drafts']);

        $file = ManagedFile::create([
            'user_id' => $user->id,
            'folder_id' => $folder->id,
            'original_name' => 'notes.txt',
            'stored_name' => 'notes.txt',
            'path' => 'uploads/notes.txt',
            'mime_type' => 'text/plain',
            'extension' => 'txt',
            'size' => 5,
        ]);

        $this->actingAs($user)
            ->delete(route('folders.destroy', $folder))
            ->assertRedirect(route('files.index'));

        $this->assertDatabaseMissing('file_folders', ['id' => $folder->id]);
        $this->assertDatabaseHas('managed_files', [
            'id' => $file->id,
            'folder_id' => null,
        ]);
    }
}
