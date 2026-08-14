<?php

namespace Tests\Feature\Api;

use App\Models\ManagedFile;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiShareTest extends TestCase
{
    use DatabaseTransactions;

    public function test_api_cannot_enable_sharing_on_a_password_protected_folder(): void
    {
        $user = User::factory()->create();
        $folder = $user->folders()->create(['name' => 'Vault']);
        $folder->setFolderPassword('super-secret');
        $folder->save();

        Sanctum::actingAs($user);

        $this->postJson(route('api.v1.folders.share.enable', $folder))
            ->assertStatus(422);

        $this->assertNull($folder->refresh()->share_token);
    }

    public function test_api_cannot_enable_sharing_on_a_file_inside_a_password_protected_folder(): void
    {
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

        Sanctum::actingAs($user);

        $this->postJson(route('api.v1.files.share.enable', $file))
            ->assertStatus(422);

        $this->assertNull($file->refresh()->share_token);
    }
}
