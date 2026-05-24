<?php

namespace App\Services;

use App\Models\ManagedFile;
use App\Models\ManagedFileChunk;
use App\Models\TelegramStorageGroup;
use Generator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Splits a file into chunks, encrypts each with AES-256-GCM, and scatters them
 * across the user's available Telegram storage groups. Each chunk lives as its
 * own Telegram message. Stream-decrypts on download.
 *
 * Defense in depth: chunks (ciphertext) live in Telegram; key (encrypted via
 * Laravel APP_KEY) lives in our DB; both required for reconstruction.
 */
class ProtectedFileService
{
    public const CHUNK_SIZE_BYTES = 40 * 1024 * 1024;       // 40 MB plaintext per chunk

    public const MAX_FILE_BYTES = 500 * 1024 * 1024;        // 500 MB total per protected file

    public const CIPHER = 'aes-256-gcm';

    private const IV_LENGTH = 12;

    public function __construct(
        private readonly TelegramFileStorageService $telegram,
    ) {}

    /**
     * Encrypt a pending file (already saved to disk at $file->path) and upload all chunks
     * to Telegram, scattering across $groups. On success, marks $file as STATUS_UPLOADED
     * and creates ManagedFileChunk rows.
     *
     * @param  Collection<int,TelegramStorageGroup>  $groups
     */
    public function uploadFromPendingPath(ManagedFile $file, Collection $groups): void
    {
        if ($groups->isEmpty()) {
            throw new RuntimeException('No Telegram storage groups available to scatter protected file across.');
        }

        $absolutePath = Storage::disk('local')->path($file->path);

        if (! is_file($absolutePath)) {
            throw new RuntimeException('Pending file is missing on disk: '.$file->path);
        }

        $fileSize = (int) (@filesize($absolutePath) ?: 0);

        if ($fileSize <= 0) {
            throw new RuntimeException('Pending file is empty: '.$file->path);
        }

        if ($fileSize > self::MAX_FILE_BYTES) {
            throw new RuntimeException('Protected file exceeds max size of '.(self::MAX_FILE_BYTES / 1024 / 1024).' MB.');
        }

        if (! $file->encryption_key) {
            // First time — generate key
            $file->forceFill([
                'encryption_key' => random_bytes(32),
                'encryption_method' => self::CIPHER,
                'original_size' => $fileSize,
                'chunk_count' => (int) ceil($fileSize / self::CHUNK_SIZE_BYTES),
            ])->save();
            $file->refresh();
        }

        $key = $file->encryption_key;
        $expectedChunks = (int) $file->chunk_count;

        $stream = fopen($absolutePath, 'rb');
        if ($stream === false) {
            throw new RuntimeException('Could not open pending file for reading.');
        }

        $groupsArray = $groups->values()->all();
        $uploadedChunks = 0;

        try {
            for ($i = 0; $i < $expectedChunks; $i++) {
                // Skip already-uploaded chunks (idempotent retry support)
                $existing = ManagedFileChunk::query()
                    ->where('managed_file_id', $file->id)
                    ->where('sequence', $i)
                    ->first();

                if ($existing && $existing->telegram_file_id) {
                    // Seek past this chunk's plaintext window in the source stream
                    fseek($stream, ($i + 1) * self::CHUNK_SIZE_BYTES);
                    continue;
                }

                fseek($stream, $i * self::CHUNK_SIZE_BYTES);
                $plaintext = stream_get_contents($stream, self::CHUNK_SIZE_BYTES);

                if ($plaintext === false || $plaintext === '') {
                    throw new RuntimeException("Could not read chunk #{$i} from pending file.");
                }

                $iv = random_bytes(self::IV_LENGTH);
                $authTag = '';
                $ciphertext = openssl_encrypt(
                    $plaintext,
                    self::CIPHER,
                    $key,
                    OPENSSL_RAW_DATA,
                    $iv,
                    $authTag,
                );

                if ($ciphertext === false) {
                    throw new RuntimeException("Encryption failed for chunk #{$i}.");
                }

                // Round-robin across groups; randomize starting offset so different files
                // don't all begin at group[0]
                $group = $groupsArray[($i + crc32($file->stored_name)) % count($groupsArray)];

                $chunkPath = $this->writeTempChunk($ciphertext);
                try {
                    $result = $this->telegram->sendDocumentFromPath(
                        $chunkPath,
                        // Opaque chunk filename — never reveals real name
                        Str::random(24).'.bin',
                        $group,
                        strlen($ciphertext),
                        'application/octet-stream',
                    );
                } finally {
                    @unlink($chunkPath);
                }

                ManagedFileChunk::updateOrCreate(
                    ['managed_file_id' => $file->id, 'sequence' => $i],
                    [
                        'telegram_storage_group_id' => $group->id,
                        'telegram_bot_token_id' => $group->telegram_bot_token_id,
                        'telegram_chat_id' => $result['chat_id'],
                        'telegram_message_id' => $result['message_id'],
                        'telegram_file_id' => $result['file_id'],
                        'telegram_file_unique_id' => $result['file_unique_id'],
                        'iv' => $iv,
                        'auth_tag' => $authTag,
                        'encrypted_size' => strlen($ciphertext),
                        'plaintext_size' => strlen($plaintext),
                    ]
                );

                $uploadedChunks++;
            }
        } finally {
            fclose($stream);
        }

        // All chunks uploaded — flip file to uploaded state and drop pending source
        $file->forceFill([
            'status' => ManagedFile::STATUS_UPLOADED,
            'upload_failure_reason' => null,
            'path' => 'telegram-protected/'.$file->user_id.'/'.$file->stored_name,
        ])->save();

        @unlink($absolutePath);
    }

    /**
     * Stream-decrypt all chunks of a protected file in order.
     * Yields plaintext bytes — caller `echo`s them into the HTTP response.
     */
    public function streamDecrypted(ManagedFile $file): Generator
    {
        if (! $file->is_protected) {
            throw new RuntimeException('Not a protected file.');
        }

        $key = $file->encryption_key;
        if (! $key) {
            throw new RuntimeException('Encryption key missing for protected file '.$file->id);
        }

        $method = $file->encryption_method ?: self::CIPHER;

        $chunks = $file->chunks()->with('telegramBotToken')->get();

        if ($chunks->count() !== (int) $file->chunk_count) {
            throw new RuntimeException("Chunk count mismatch: expected {$file->chunk_count}, got {$chunks->count()}");
        }

        foreach ($chunks as $chunk) {
            $bot = $chunk->telegramBotToken;
            if (! $bot || ! $chunk->telegram_file_id) {
                throw new RuntimeException("Chunk #{$chunk->sequence} is missing Telegram metadata.");
            }

            $ciphertext = $this->telegram->downloadFileBytes($bot, $chunk->telegram_file_id);

            $plaintext = openssl_decrypt(
                $ciphertext,
                $method,
                $key,
                OPENSSL_RAW_DATA,
                (string) $chunk->iv,
                (string) $chunk->auth_tag,
            );

            if ($plaintext === false) {
                throw new RuntimeException("Decryption failed for chunk #{$chunk->sequence} (file {$file->id}).");
            }

            yield $plaintext;
        }
    }

    /**
     * Delete all chunks from Telegram (best-effort) and the chunk rows from DB.
     */
    public function deleteChunks(ManagedFile $file): void
    {
        $file->loadMissing('chunks.telegramBotToken');

        foreach ($file->chunks as $chunk) {
            $bot = $chunk->telegramBotToken;

            if (! $bot || ! $chunk->telegram_chat_id || ! $chunk->telegram_message_id) {
                continue;
            }

            try {
                $this->telegram->deleteMessageRaw($bot, $chunk->telegram_chat_id, $chunk->telegram_message_id);
            } catch (Throwable $exception) {
                // best-effort; chunk row deletion still happens via cascade when file is deleted
                report($exception);
            }
        }

        $file->chunks()->delete();
    }

    private function writeTempChunk(string $ciphertext): string
    {
        $directory = storage_path('app/protected-chunks-tmp');

        if (! is_dir($directory)) {
            @mkdir($directory, 0775, true);
        }

        $path = $directory.'/'.Str::uuid().'.bin';

        if (file_put_contents($path, $ciphertext) === false) {
            throw new RuntimeException('Could not write temporary chunk file.');
        }

        return $path;
    }
}
