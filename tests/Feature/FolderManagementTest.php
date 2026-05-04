<?php

namespace Tests\Feature;

use App\Models\FileFolder;
use App\Models\ManagedFile;
use App\Models\TelegramBotToken;
use App\Models\TelegramStorageGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
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

    public function test_folder_delete_removes_file_records_and_local_files(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('uploads/notes.txt', 'notes');

        $user = User::factory()->create();
        $folder = $user->folders()->create(['name' => 'Drafts']);

        $file = ManagedFile::create([
            'user_id' => $user->id,
            'folder_id' => $folder->id,
            'storage_driver' => 'local',
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
        $this->assertDatabaseMissing('managed_files', ['id' => $file->id]);
        Storage::disk('local')->assertMissing('uploads/notes.txt');
    }

    public function test_folder_delete_forgets_telegram_file_records_without_deleting_group_messages(): void
    {
        Http::fake();

        $user = User::factory()->create();
        $folder = $user->folders()->create(['name' => 'Archive']);
        $bot = TelegramBotToken::create([
            'user_id' => $user->id,
            'name' => 'Storage Bot',
            'token' => '123456:ABCDEF',
        ]);
        $group = TelegramStorageGroup::create([
            'user_id' => $user->id,
            'telegram_bot_token_id' => $bot->id,
            'title' => 'Archive Group',
            'chat_id' => '-1001234567890',
        ]);
        $file = ManagedFile::create([
            'user_id' => $user->id,
            'folder_id' => $folder->id,
            'storage_driver' => 'telegram',
            'telegram_bot_token_id' => $bot->id,
            'telegram_storage_group_id' => $group->id,
            'telegram_chat_id' => '-1001234567890',
            'telegram_message_id' => 777,
            'telegram_file_id' => 'telegram-file-id',
            'original_name' => 'notes.txt',
            'stored_name' => 'notes.txt',
            'path' => 'telegram/'.$user->id.'/notes.txt',
            'mime_type' => 'text/plain',
            'extension' => 'txt',
            'size' => 5,
        ]);

        $this->actingAs($user)
            ->delete(route('folders.destroy', $folder))
            ->assertRedirect(route('files.index'));

        $this->assertDatabaseMissing('file_folders', ['id' => $folder->id]);
        $this->assertDatabaseMissing('managed_files', ['id' => $file->id]);
        Http::assertSentCount(0);
    }
}
