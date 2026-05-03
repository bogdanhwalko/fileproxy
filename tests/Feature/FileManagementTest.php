<?php

namespace Tests\Feature;

use App\Models\ManagedFile;
use App\Models\TelegramBotToken;
use App\Models\TelegramStorageGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FileManagementTest extends TestCase
{
    use DatabaseTransactions;

    public function test_file_can_be_uploaded(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($user)->post(route('files.store'), [
            'files' => [
                UploadedFile::fake()->create('contract.pdf', 128, 'application/pdf'),
            ],
        ]);

        $response->assertRedirect(route('files.index'));

        $this->assertDatabaseHas('managed_files', [
            'user_id' => $user->id,
            'original_name' => 'contract.pdf',
            'extension' => 'pdf',
            'mime_type' => 'application/pdf',
            'storage_driver' => 'local',
        ]);

        $file = ManagedFile::where('original_name', 'contract.pdf')->firstOrFail();

        Storage::disk('local')->assertExists($file->path);
    }

    public function test_files_index_initially_shows_twenty_files_with_load_more(): void
    {
        $user = User::factory()->create(['is_admin' => true]);

        for ($i = 1; $i <= 25; $i++) {
            ManagedFile::create([
                'user_id' => $user->id,
                'storage_driver' => 'local',
                'original_name' => "file-{$i}.txt",
                'stored_name' => "file-{$i}.txt",
                'path' => "uploads/file-{$i}.txt",
                'mime_type' => 'text/plain',
                'extension' => 'txt',
                'size' => 10,
                'created_at' => now()->addSeconds($i),
                'updated_at' => now()->addSeconds($i),
            ]);
        }

        $response = $this->actingAs($user)
            ->get(route('files.index'));

        $response
            ->assertOk()
            ->assertSee('data-upload-form', false)
            ->assertSee('data-upload-progress', false)
            ->assertSee('data-ajax-form', false)
            ->assertSee('compact-file-table')
            ->assertSee('Завантажити ще')
            ->assertSee('Показано 20 з 25');

        preg_match_all('/\sdata-file-item(?:\s|>)/', $response->getContent(), $matches);

        $this->assertCount(20, $matches[0]);
    }

    public function test_files_index_can_be_displayed_as_tiles(): void
    {
        $user = User::factory()->create(['is_admin' => true]);

        ManagedFile::create([
            'user_id' => $user->id,
            'storage_driver' => 'local',
            'original_name' => 'tile.txt',
            'stored_name' => 'tile.txt',
            'path' => 'uploads/tile.txt',
            'mime_type' => 'text/plain',
            'extension' => 'txt',
            'size' => 10,
        ]);

        $this->actingAs($user)
            ->get(route('files.index', ['view' => 'grid']))
            ->assertOk()
            ->assertSee('file-grid')
            ->assertSee('file-tile')
            ->assertSee('Плитки');
    }

    public function test_grid_view_can_preload_image_previews_for_current_page(): void
    {
        $user = User::factory()->create(['is_admin' => true]);

        for ($i = 1; $i <= 21; $i++) {
            ManagedFile::create([
                'user_id' => $user->id,
                'storage_driver' => 'local',
                'original_name' => "photo-{$i}.png",
                'stored_name' => "photo-{$i}.png",
                'path' => "uploads/photo-{$i}.png",
                'mime_type' => 'image/png',
                'extension' => 'png',
                'size' => 10,
                'created_at' => now()->addSeconds($i),
                'updated_at' => now()->addSeconds($i),
            ]);
        }

        $withoutPreload = $this->actingAs($user)
            ->get(route('files.index', ['view' => 'grid']));

        $withoutPreload
            ->assertOk()
            ->assertSee('Передзавантажити фото')
            ->assertDontSee('class="file-tile-preview"', false);

        $response = $this->actingAs($user)
            ->get(route('files.index', [
                'view' => 'grid',
                'image_previews' => 1,
            ]));

        $response
            ->assertOk()
            ->assertSee('Фото увімкнено')
            ->assertSee('image_previews=1', false);

        $this->assertMatchesRegularExpression(
            '/href="[^"]*(?:image_previews=1[^"]*page=2|page=2[^"]*image_previews=1)[^"]*"[^>]*data-load-more-link/',
            $response->getContent()
        );

        preg_match_all('/class="file-tile-preview"/', $response->getContent(), $matches);

        $this->assertCount(20, $matches[0]);
    }

    public function test_grid_view_keeps_preview_slot_for_non_images_when_previews_are_enabled(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $image = ManagedFile::create([
            'user_id' => $user->id,
            'storage_driver' => 'local',
            'original_name' => 'photo.png',
            'stored_name' => 'photo.png',
            'path' => 'uploads/photo.png',
            'mime_type' => 'image/png',
            'extension' => 'png',
            'size' => 10,
        ]);
        $document = ManagedFile::create([
            'user_id' => $user->id,
            'storage_driver' => 'local',
            'original_name' => 'contract.pdf',
            'stored_name' => 'contract.pdf',
            'path' => 'uploads/contract.pdf',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'size' => 10,
        ]);

        $response = $this->actingAs($user)
            ->get(route('files.index', [
                'view' => 'grid',
                'image_previews' => 1,
            ]));

        $response
            ->assertOk()
            ->assertSee('file-tile-preview file-tile-preview-empty', false)
            ->assertSee(route('files.inline', $image), false)
            ->assertDontSee(route('files.inline', $document), false);
    }

    public function test_regular_user_cannot_upload_without_telegram_group(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)->post(route('files.store'), [
            'files' => [
                UploadedFile::fake()->create('contract.pdf', 128, 'application/pdf'),
            ],
        ])
            ->assertSessionHasErrors('telegram_storage_group_id');

        $this->assertDatabaseMissing('managed_files', [
            'user_id' => $user->id,
            'original_name' => 'contract.pdf',
        ]);
    }

    public function test_regular_user_without_own_group_can_upload_to_system_telegram_storage(): void
    {
        Storage::fake('local');
        Http::fake([
            'https://api.telegram.org/bot123456:ABCDEF/sendDocument' => Http::response([
                'ok' => true,
                'result' => [
                    'message_id' => 900,
                    'chat' => ['id' => -1001234567890],
                    'document' => [
                        'file_id' => 'system-file-id',
                        'file_unique_id' => 'system-unique-id',
                        'file_name' => 'contract.pdf',
                        'mime_type' => 'application/pdf',
                        'file_size' => 131072,
                    ],
                ],
            ]),
        ]);

        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create(['is_admin' => false]);
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
            'is_global_default' => true,
        ]);

        $response = $this->actingAs($user)->post(route('files.store'), [
            'files' => [
                UploadedFile::fake()->create('contract.pdf', 128, 'application/pdf'),
            ],
        ]);

        $response->assertRedirect(route('files.index'));

        $file = ManagedFile::where('original_name', 'contract.pdf')->firstOrFail();

        $this->assertSame($user->id, $file->user_id);
        $this->assertSame('telegram', $file->storage_driver);
        $this->assertSame($bot->id, $file->telegram_bot_token_id);
        $this->assertSame($group->id, $file->telegram_storage_group_id);
        $this->assertSame(900, $file->telegram_message_id);

        $this->actingAs($user)
            ->get(route('files.index'))
            ->assertOk()
            ->assertSee('Системне Telegram-сховище')
            ->assertSee('Залишилось 99 з 100');
    }

    public function test_system_telegram_storage_distributes_uploads_between_default_groups(): void
    {
        Storage::fake('local');
        Http::fake(function ($request) {
            $isSecondBot = str_contains($request->url(), 'bot222:BBB');

            return Http::response([
                'ok' => true,
                'result' => [
                    'message_id' => $isSecondBot ? 222 : 111,
                    'chat' => ['id' => $isSecondBot ? -100222 : -100111],
                    'document' => [
                        'file_id' => $isSecondBot ? 'second-file-id' : 'first-file-id',
                        'file_unique_id' => $isSecondBot ? 'second-unique-id' : 'first-unique-id',
                        'file_name' => 'file.txt',
                        'mime_type' => 'text/plain',
                        'file_size' => 1024,
                    ],
                ],
            ]);
        });

        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create(['is_admin' => false]);
        $firstBot = TelegramBotToken::create([
            'user_id' => $admin->id,
            'name' => 'First System Bot',
            'token' => '111:AAA',
        ]);
        $secondBot = TelegramBotToken::create([
            'user_id' => $admin->id,
            'name' => 'Second System Bot',
            'token' => '222:BBB',
        ]);
        $firstGroup = TelegramStorageGroup::create([
            'user_id' => $admin->id,
            'telegram_bot_token_id' => $firstBot->id,
            'title' => 'First System Archive',
            'chat_id' => '-100111',
            'is_global_default' => true,
        ]);
        $secondGroup = TelegramStorageGroup::create([
            'user_id' => $admin->id,
            'telegram_bot_token_id' => $secondBot->id,
            'title' => 'Second System Archive',
            'chat_id' => '-100222',
            'is_global_default' => true,
        ]);

        $this->actingAs($user)->post(route('files.store'), [
            'files' => [
                UploadedFile::fake()->create('one.txt', 1, 'text/plain'),
                UploadedFile::fake()->create('two.txt', 1, 'text/plain'),
                UploadedFile::fake()->create('three.txt', 1, 'text/plain'),
            ],
        ])->assertRedirect(route('files.index'));

        $this->assertSame(2, ManagedFile::where('user_id', $user->id)->where('telegram_storage_group_id', $firstGroup->id)->count());
        $this->assertSame(1, ManagedFile::where('user_id', $user->id)->where('telegram_storage_group_id', $secondGroup->id)->count());
        Http::assertSentCount(3);
    }

    public function test_system_telegram_storage_stops_after_one_hundred_files(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create(['is_admin' => false]);
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
            'is_global_default' => true,
        ]);

        for ($i = 1; $i <= 100; $i++) {
            ManagedFile::create([
                'user_id' => $user->id,
                'storage_driver' => 'telegram',
                'telegram_bot_token_id' => $bot->id,
                'telegram_storage_group_id' => $group->id,
                'telegram_chat_id' => '-1001234567890',
                'telegram_message_id' => $i,
                'telegram_file_id' => "file-{$i}",
                'original_name' => "existing-{$i}.txt",
                'stored_name' => "existing-{$i}.txt",
                'path' => "telegram/{$user->id}/existing-{$i}.txt",
                'mime_type' => 'text/plain',
                'extension' => 'txt',
                'size' => 10,
            ]);
        }

        $this->actingAs($user)->post(route('files.store'), [
            'files' => [
                UploadedFile::fake()->create('extra.txt', 1, 'text/plain'),
            ],
        ])->assertSessionHasErrors('telegram_storage_group_id');

        $this->assertDatabaseMissing('managed_files', [
            'user_id' => $user->id,
            'original_name' => 'extra.txt',
        ]);
    }

    public function test_upload_rejects_files_larger_than_telegram_limit(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['is_admin' => true]);

        $this->actingAs($user)->post(route('files.store'), [
            'files' => [
                UploadedFile::fake()->create('large.zip', 51201, 'application/zip'),
            ],
        ])
            ->assertSessionHasErrors('files.0');

        $this->assertDatabaseMissing('managed_files', [
            'user_id' => $user->id,
            'original_name' => 'large.zip',
        ]);
    }

    public function test_file_can_be_uploaded_to_telegram_group(): void
    {
        Storage::fake('local');
        Http::fake([
            'https://api.telegram.org/bot123456:ABCDEF/sendDocument' => Http::response([
                'ok' => true,
                'result' => [
                    'message_id' => 777,
                    'chat' => ['id' => -1001234567890],
                    'document' => [
                        'file_id' => 'telegram-file-id',
                        'file_unique_id' => 'telegram-unique-id',
                        'file_name' => 'contract.pdf',
                        'mime_type' => 'application/pdf',
                        'file_size' => 131072,
                    ],
                ],
            ]),
        ]);

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

        $response = $this->actingAs($user)->post(route('files.store'), [
            'telegram_storage_group_id' => $group->id,
            'files' => [
                UploadedFile::fake()->create('contract.pdf', 128, 'application/pdf'),
            ],
        ]);

        $response->assertRedirect(route('files.index'));

        $file = ManagedFile::where('original_name', 'contract.pdf')->firstOrFail();

        $this->assertSame('telegram', $file->storage_driver);
        $this->assertSame($bot->id, $file->telegram_bot_token_id);
        $this->assertSame($group->id, $file->telegram_storage_group_id);
        $this->assertSame('-1001234567890', $file->telegram_chat_id);
        $this->assertSame(777, $file->telegram_message_id);
        $this->assertSame('telegram-file-id', $file->telegram_file_id);
        Storage::disk('local')->assertMissing($file->path);

        Http::assertSent(fn ($request) => $request->url() === 'https://api.telegram.org/bot123456:ABCDEF/sendDocument');
    }

    public function test_telegram_text_file_is_downloaded_temporarily_for_preview(): void
    {
        Storage::fake('local');
        Http::fake([
            'https://api.telegram.org/bot123456:ABCDEF/getFile' => Http::response([
                'ok' => true,
                'result' => [
                    'file_path' => 'documents/readme.txt',
                ],
            ]),
            'https://api.telegram.org/file/bot123456:ABCDEF/documents/readme.txt' => Http::response("Hello\nTelegram storage"),
        ]);

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
        $file = ManagedFile::create([
            'user_id' => $user->id,
            'storage_driver' => 'telegram',
            'telegram_bot_token_id' => $bot->id,
            'telegram_storage_group_id' => $group->id,
            'telegram_chat_id' => '-1001234567890',
            'telegram_message_id' => 777,
            'telegram_file_id' => 'telegram-file-id',
            'telegram_file_unique_id' => 'telegram-unique-id',
            'original_name' => 'readme.txt',
            'stored_name' => 'readme.txt',
            'path' => 'telegram/'.$user->id.'/readme.txt',
            'mime_type' => 'text/plain',
            'extension' => 'txt',
            'size' => 22,
        ]);

        $this->actingAs($user)
            ->get(route('files.preview', $file))
            ->assertOk()
            ->assertSee('Hello')
            ->assertSee('Telegram storage');

        $this->assertSame([], Storage::disk('local')->allFiles('telegram-temp/'.$user->id));
    }

    public function test_file_can_be_deleted(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('uploads/report.txt', 'demo');
        $user = User::factory()->create();

        $file = ManagedFile::create([
            'user_id' => $user->id,
            'original_name' => 'report.txt',
            'stored_name' => 'report.txt',
            'path' => 'uploads/report.txt',
            'mime_type' => 'text/plain',
            'extension' => 'txt',
            'size' => 4,
        ]);

        $response = $this->actingAs($user)->delete(route('files.destroy', $file));

        $response->assertRedirect(route('files.index'));
        Storage::disk('local')->assertMissing('uploads/report.txt');
        $this->assertDatabaseMissing('managed_files', ['id' => $file->id]);
    }

    public function test_text_file_can_be_previewed(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('uploads/readme.txt', "Hello\nFileProxy");
        $user = User::factory()->create();

        $file = ManagedFile::create([
            'user_id' => $user->id,
            'original_name' => 'readme.txt',
            'stored_name' => 'readme.txt',
            'path' => 'uploads/readme.txt',
            'mime_type' => 'text/plain',
            'extension' => 'txt',
            'size' => 15,
        ]);

        $this->actingAs($user)
            ->get(route('files.preview', $file))
            ->assertOk()
            ->assertSee('Hello')
            ->assertSee('FileProxy');
    }

    public function test_image_file_can_be_previewed_inline(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('uploads/avatar.png', 'fake image');
        $user = User::factory()->create();

        $file = ManagedFile::create([
            'user_id' => $user->id,
            'original_name' => 'avatar.png',
            'stored_name' => 'avatar.png',
            'path' => 'uploads/avatar.png',
            'mime_type' => 'image/png',
            'extension' => 'png',
            'size' => 10,
        ]);

        $this->actingAs($user)
            ->get(route('files.preview', $file))
            ->assertOk()
            ->assertSee(route('files.inline', $file), false);

        $this->actingAs($user)
            ->get(route('files.inline', $file))
            ->assertOk()
            ->assertHeader('content-type', 'image/png');
    }

    public function test_telegram_image_file_is_streamed_inline_without_temp_file(): void
    {
        Storage::fake('local');
        Http::fake([
            'https://api.telegram.org/bot123456:ABCDEF/getFile' => Http::response([
                'ok' => true,
                'result' => [
                    'file_path' => 'photos/avatar.png',
                ],
            ]),
            'https://api.telegram.org/file/bot123456:ABCDEF/photos/avatar.png' => Http::response('telegram image bytes', 200, [
                'Content-Type' => 'image/png',
            ]),
        ]);

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
        $file = ManagedFile::create([
            'user_id' => $user->id,
            'storage_driver' => 'telegram',
            'telegram_bot_token_id' => $bot->id,
            'telegram_storage_group_id' => $group->id,
            'telegram_chat_id' => '-1001234567890',
            'telegram_message_id' => 777,
            'telegram_file_id' => 'telegram-file-id',
            'telegram_file_unique_id' => 'telegram-unique-id',
            'original_name' => 'avatar.png',
            'stored_name' => 'avatar.png',
            'path' => 'telegram/'.$user->id.'/avatar.png',
            'mime_type' => 'image/png',
            'extension' => 'png',
            'size' => 20,
        ]);

        $response = $this->actingAs($user)->get(route('files.inline', $file));

        $response
            ->assertOk()
            ->assertHeader('content-type', 'image/png');

        $this->assertSame('telegram image bytes', $response->streamedContent());
        $this->assertSame([], Storage::disk('local')->allFiles('telegram-temp/'.$user->id));
        Http::assertSent(fn ($request) => $request->url() === 'https://api.telegram.org/bot123456:ABCDEF/getFile');
        Http::assertSent(fn ($request) => $request->url() === 'https://api.telegram.org/file/bot123456:ABCDEF/photos/avatar.png');
    }
}
