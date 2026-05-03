<?php

namespace App\Services;

use App\Models\ManagedFile;
use App\Models\TelegramStorageGroup;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ManagedFileStorageService
{
    public function __construct(private readonly TelegramFileStorageService $telegram) {}

    public function storeUploadedFile(
        User $user,
        UploadedFile $uploadedFile,
        ?int $folderId = null,
        ?TelegramStorageGroup $telegramGroup = null
    ): ManagedFile {
        $extension = strtolower($uploadedFile->getClientOriginalExtension());
        $storedName = (string) Str::uuid();

        if ($extension !== '') {
            $storedName .= ".{$extension}";
        }

        if ($telegramGroup) {
            $telegram = $this->telegram->sendDocument($uploadedFile, $telegramGroup);

            return ManagedFile::create([
                'user_id' => $user->id,
                'folder_id' => $folderId,
                'storage_driver' => 'telegram',
                'telegram_bot_token_id' => $telegramGroup->telegram_bot_token_id,
                'telegram_storage_group_id' => $telegramGroup->id,
                'telegram_chat_id' => $telegram['chat_id'],
                'telegram_message_id' => $telegram['message_id'],
                'telegram_file_id' => $telegram['file_id'],
                'telegram_file_unique_id' => $telegram['file_unique_id'],
                'telegram_response' => $telegram['payload'],
                'original_name' => $uploadedFile->getClientOriginalName(),
                'stored_name' => $storedName,
                'path' => 'telegram/'.$user->id.'/'.$storedName,
                'mime_type' => $telegram['mime_type'],
                'extension' => $extension ?: null,
                'size' => $telegram['file_size'],
            ]);
        }

        $storageDirectory = 'uploads/'.$user->id.'/'.($folderId ? "folders/{$folderId}" : 'root');
        $path = $uploadedFile->storeAs($storageDirectory, $storedName, 'local');

        return ManagedFile::create([
            'user_id' => $user->id,
            'folder_id' => $folderId,
            'storage_driver' => 'local',
            'original_name' => $uploadedFile->getClientOriginalName(),
            'stored_name' => $storedName,
            'path' => $path,
            'mime_type' => $uploadedFile->getMimeType() ?: 'application/octet-stream',
            'extension' => $extension ?: null,
            'size' => $uploadedFile->getSize() ?: 0,
        ]);
    }

    public function exists(ManagedFile $file): bool
    {
        if ($file->is_telegram) {
            return (bool) ($file->telegramBotToken && $file->telegram_file_id);
        }

        return Storage::disk('local')->exists($file->path);
    }

    public function downloadResponse(ManagedFile $file): StreamedResponse|BinaryFileResponse
    {
        if (! $file->is_telegram) {
            return Storage::disk('local')->download($file->path, $file->original_name, [
                'Content-Type' => $file->mime_type ?: 'application/octet-stream',
            ]);
        }

        $temporaryPath = $this->telegram->downloadToTemporaryPath($file);

        return response()
            ->download($temporaryPath, $file->original_name, [
                'Content-Type' => $file->mime_type ?: 'application/octet-stream',
            ])
            ->deleteFileAfterSend();
    }

    public function inlineResponse(ManagedFile $file): StreamedResponse|BinaryFileResponse
    {
        if (! $file->is_telegram) {
            return Storage::disk('local')->response($file->path, $file->original_name, [
                'Content-Type' => $file->mime_type ?: 'application/octet-stream',
            ]);
        }

        $stream = $this->telegram->downloadStream($file);

        return response()->stream(function () use ($stream): void {
            try {
                while (! $stream->eof()) {
                    echo $stream->read(1024 * 1024);

                    if (ob_get_level() > 0) {
                        @ob_flush();
                    }

                    flush();
                }
            } finally {
                $stream->close();
            }
        }, 200, [
            'Content-Type' => $file->mime_type ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="'.$file->original_name.'"',
        ]);
    }

    public function readTextPreview(ManagedFile $file): array
    {
        $temporaryPath = null;

        if ($file->is_telegram) {
            $temporaryPath = $this->telegram->downloadToTemporaryPath($file);
            $stream = fopen($temporaryPath, 'r');
        } else {
            $stream = Storage::disk('local')->readStream($file->path);
        }

        if ($stream === false) {
            abort(404);
        }

        $content = stream_get_contents($stream, 1024 * 1024 + 1);
        fclose($stream);

        if ($temporaryPath) {
            @unlink($temporaryPath);
        }

        abort_unless($content !== false, 404);

        $isTruncated = strlen($content) > 1024 * 1024;

        if ($isTruncated) {
            $content = substr($content, 0, 1024 * 1024);
        }

        return [$content, $isTruncated];
    }

    public function temporaryPathForArchive(ManagedFile $file): array
    {
        if ($file->is_telegram) {
            return [$this->telegram->downloadToTemporaryPath($file), true];
        }

        return [Storage::disk('local')->path($file->path), false];
    }

    public function delete(ManagedFile $file): void
    {
        if ($file->is_telegram) {
            $this->telegram->deleteMessage($file);
        } else {
            Storage::disk('local')->delete($file->path);
        }

        $file->delete();
    }
}
