<?php

namespace App\Console\Commands;

use App\Models\ManagedFile;
use App\Services\TelegramFileStorageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Throwable;

class VerifyProtectedFileCommand extends Command
{
    protected $signature = 'files:verify-protected {id : ManagedFile ID}';

    protected $description = 'Download + decrypt all chunks of a protected file and verify byte counts match';

    public function handle(TelegramFileStorageService $telegram): int
    {
        $id = (int) $this->argument('id');
        $file = ManagedFile::with('chunks.telegramBotToken')->find($id);

        if (! $file) {
            $this->error("File #{$id} not found");
            return 1;
        }

        $this->info("=== File #{$file->id}: {$file->original_name} ===");
        $this->line('status:           '.$file->status);
        $this->line('is_protected:     '.($file->is_protected ? 'yes' : 'no'));
        $this->line('original_size:    '.$file->original_size.' bytes');
        $this->line('chunk_count:      '.$file->chunk_count);
        $this->line('chunks in DB:     '.$file->chunks->count());
        $this->line('encryption_method: '.($file->encryption_method ?: '(none)'));
        $this->line('key length:       '.strlen((string) $file->encryption_key).' bytes');
        $this->newLine();

        if (! $file->is_protected) {
            $this->warn('Not a protected file — nothing to verify.');
            return 0;
        }

        if ($file->chunks->count() !== (int) $file->chunk_count) {
            $this->error('⚠️  Chunk count mismatch: declared '.$file->chunk_count.', actual rows '.$file->chunks->count());
        }

        $key = $file->encryption_key;
        $method = $file->encryption_method ?: 'aes-256-gcm';
        $totalPlaintext = 0;
        $totalCiphertext = 0;
        $errors = [];

        foreach ($file->chunks->sortBy('sequence') as $chunk) {
            $this->info("--- Chunk #{$chunk->sequence} ---");
            $this->line('iv length:          '.strlen((string) $chunk->iv).' bytes  (expected 12)');
            $this->line('auth_tag length:    '.strlen((string) $chunk->auth_tag).' bytes  (expected 16)');
            $this->line('iv hex:             '.bin2hex((string) $chunk->iv));
            $this->line('auth_tag hex:       '.bin2hex((string) $chunk->auth_tag));
            $this->line('declared encrypted: '.$chunk->encrypted_size.' bytes');
            $this->line('declared plaintext: '.$chunk->plaintext_size.' bytes');
            $this->line('telegram_file_id:   '.substr((string) $chunk->telegram_file_id, 0, 30).'...');

            try {
                $bot = $chunk->telegramBotToken;
                if (! $bot) {
                    $this->error('  ✗ Bot record missing for chunk');
                    $errors[] = "Chunk #{$chunk->sequence}: missing bot";
                    continue;
                }

                // Probe getFile directly so we can see EXACTLY what Telegram returns
                $this->line('bot token id:       '.$bot->id.'  (name: '.$bot->name.')');
                $probe = Http::asJson()
                    ->timeout(15)
                    ->post('https://api.telegram.org/bot'.$bot->token.'/getFile', [
                        'file_id' => $chunk->telegram_file_id,
                    ]);
                $this->line('getFile HTTP:       '.$probe->status());
                $this->line('getFile body:       '.substr((string) $probe->body(), 0, 300));

                if (! $probe->successful() || ! $probe->json('ok')) {
                    $errors[] = "Chunk #{$chunk->sequence}: getFile failed — ".substr((string) $probe->body(), 0, 200);
                    $this->newLine();
                    continue;
                }

                $cipher = $telegram->downloadFileBytes($bot, $chunk->telegram_file_id);
                $this->line('downloaded bytes:   '.strlen($cipher));

                if (strlen($cipher) !== (int) $chunk->encrypted_size) {
                    $this->error('  ⚠️  Downloaded size differs from declared encrypted_size');
                    $errors[] = "Chunk #{$chunk->sequence}: download size mismatch (got ".strlen($cipher).", declared {$chunk->encrypted_size})";
                }
                $totalCiphertext += strlen($cipher);

                $plain = openssl_decrypt(
                    $cipher,
                    $method,
                    $key,
                    OPENSSL_RAW_DATA,
                    (string) $chunk->iv,
                    (string) $chunk->auth_tag,
                );

                if ($plain === false) {
                    $this->error('  ✗ Decryption FAILED (openssl_decrypt returned false)');
                    $errors[] = "Chunk #{$chunk->sequence}: decryption failed";
                } else {
                    $this->line('decrypted bytes:    '.strlen($plain));

                    if (strlen($plain) !== (int) $chunk->plaintext_size) {
                        $this->error('  ⚠️  Decrypted size differs from declared plaintext_size');
                        $errors[] = "Chunk #{$chunk->sequence}: decrypted size mismatch (got ".strlen($plain).", declared {$chunk->plaintext_size})";
                    }

                    if ($chunk->sequence === 0) {
                        $this->line('first 32 bytes hex: '.bin2hex(substr($plain, 0, 32)));
                    }

                    $totalPlaintext += strlen($plain);
                }
            } catch (Throwable $e) {
                $this->error('  ERROR: '.$e->getMessage());
                $errors[] = "Chunk #{$chunk->sequence}: ".$e->getMessage();
            }

            $this->newLine();
        }

        $this->info('=== Summary ===');
        $this->line('Sum of ciphertext bytes:  '.$totalCiphertext);
        $this->line('Sum of plaintext bytes:   '.$totalPlaintext);
        $this->line('Declared original_size:   '.$file->original_size);

        if ($totalPlaintext !== (int) $file->original_size) {
            $diff = $totalPlaintext - (int) $file->original_size;
            $this->error('⚠️  Byte count mismatch: diff = '.$diff.' bytes');
        } else {
            $this->info('✓ Plaintext sum matches original_size');
        }

        if ($errors) {
            $this->newLine();
            $this->error('Errors:');
            foreach ($errors as $e) {
                $this->error('  - '.$e);
            }
            return 1;
        }

        $this->info('✓ All chunks verified successfully — encryption/storage pipeline is intact.');
        $this->warn('If file still corrupts on download, the issue is in the streaming response (not encryption).');

        return 0;
    }
}
