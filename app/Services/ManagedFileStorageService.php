<?php

namespace App\Services;

use App\Jobs\UploadManagedFileToTelegram;
use App\Models\ManagedFile;
use App\Models\TelegramStorageGroup;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\HeaderUtils;
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
        $extension = preg_replace('/[^a-z0-9]+/', '', strtolower($uploadedFile->getClientOriginalExtension())) ?? '';
        $storedName = (string) Str::uuid();

        if ($extension !== '') {
            $storedName .= ".{$extension}";
        }

        if ($telegramGroup) {
            $pendingDirectory = 'uploads-pending/'.$user->id;
            $pendingPath = $uploadedFile->storeAs($pendingDirectory, $storedName, 'local');

            $file = ManagedFile::create([
                'user_id' => $user->id,
                'folder_id' => $folderId,
                'storage_driver' => 'telegram',
                'telegram_bot_token_id' => $telegramGroup->telegram_bot_token_id,
                'telegram_storage_group_id' => $telegramGroup->id,
                'original_name' => $uploadedFile->getClientOriginalName(),
                'stored_name' => $storedName,
                'path' => $pendingPath,
                'mime_type' => $uploadedFile->getMimeType() ?: 'application/octet-stream',
                'extension' => $extension ?: null,
                'size' => $uploadedFile->getSize() ?: 0,
                'status' => ManagedFile::STATUS_PENDING,
            ]);

            UploadManagedFileToTelegram::dispatch($file);

            return $file;
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
        if ($file->is_pending || $file->is_failed) {
            return false;
        }

        if ($file->is_telegram) {
            return (bool) ($file->telegramBotToken && $file->telegram_file_id);
        }

        return Storage::disk('local')->exists($file->path);
    }

    public function downloadResponse(ManagedFile $file): StreamedResponse|BinaryFileResponse|Response
    {
        $downloadHeaders = [
            'Content-Type' => $file->mime_type ?: 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
        ];

        if (! $file->is_telegram) {
            if ($accel = $this->xAccelResponse($file, HeaderUtils::DISPOSITION_ATTACHMENT)) {
                return $accel;
            }

            return Storage::disk('local')->download($file->path, $file->original_name, $downloadHeaders);
        }

        $temporaryPath = $this->telegram->downloadToTemporaryPath($file);

        return response()
            ->download($temporaryPath, $file->original_name, $downloadHeaders)
            ->deleteFileAfterSend();
    }

    public function inlineResponse(ManagedFile $file): StreamedResponse|BinaryFileResponse|Response
    {
        $inlineHeaders = $this->inlineSecurityHeaders($file->mime_type ?: 'application/octet-stream');

        if (! $file->is_telegram) {
            if ($accel = $this->xAccelResponse($file, HeaderUtils::DISPOSITION_INLINE)) {
                return $accel;
            }

            return Storage::disk('local')->response($file->path, $file->original_name, $inlineHeaders);
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
        }, 200, $inlineHeaders + [
            'Content-Disposition' => HeaderUtils::makeDisposition(
                HeaderUtils::DISPOSITION_INLINE,
                $this->safeDownloadName($file->original_name),
                $this->asciiDownloadFallback($file->original_name)
            ),
        ]);
    }

    public function downloadLocalPathResponse(string $absolutePath, string $downloadName, string $contentType = 'application/zip'): Response|BinaryFileResponse
    {
        if ($accel = $this->xAccelResponseForAbsolutePath($absolutePath, $downloadName, $contentType, HeaderUtils::DISPOSITION_ATTACHMENT)) {
            return $accel;
        }

        return response()
            ->download($absolutePath, $downloadName, ['Content-Type' => $contentType])
            ->deleteFileAfterSend();
    }

    private function xAccelResponse(ManagedFile $file, string $disposition): ?Response
    {
        return $this->xAccelResponseForAbsolutePath(
            Storage::disk('local')->path($file->path),
            $file->original_name,
            $file->mime_type ?: 'application/octet-stream',
            $disposition
        );
    }

    private function xAccelResponseForAbsolutePath(
        string $absolutePath,
        string $downloadName,
        string $contentType,
        string $disposition
    ): ?Response {
        if (! $this->xAccelEnabled()) {
            return null;
        }

        $internalPath = $this->mapToInternalPath($absolutePath);

        if ($internalPath === null) {
            return null;
        }

        $headers = [
            'Content-Type' => $contentType,
            'X-Accel-Redirect' => $internalPath,
            'X-Content-Type-Options' => 'nosniff',
            'Content-Disposition' => HeaderUtils::makeDisposition(
                $disposition,
                $this->safeDownloadName($downloadName),
                $this->asciiDownloadFallback($downloadName)
            ),
        ];

        if ($disposition === HeaderUtils::DISPOSITION_INLINE) {
            $headers['Content-Security-Policy'] = $this->inlineContentSecurityPolicy();
        }

        return response('', 200, $headers);
    }

    private function inlineSecurityHeaders(string $contentType): array
    {
        return [
            'Content-Type' => $contentType,
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => $this->inlineContentSecurityPolicy(),
        ];
    }

    private function inlineContentSecurityPolicy(): string
    {
        return "default-src 'none'; img-src 'self' data:; style-src 'unsafe-inline'; sandbox";
    }

    private function xAccelEnabled(): bool
    {
        return strtolower((string) config('filesystems.sendfile_driver', env('SENDFILE_DRIVER', 'none'))) === 'nginx';
    }

    private function mapToInternalPath(string $absolutePath): ?string
    {
        $storageRoot = rtrim(Storage::disk('local')->path(''), DIRECTORY_SEPARATOR.'/');
        $absolutePath = str_replace('\\', '/', $absolutePath);
        $storageRoot = str_replace('\\', '/', $storageRoot);

        if (! str_starts_with($absolutePath, $storageRoot.'/')) {
            return null;
        }

        $relative = ltrim(substr($absolutePath, strlen($storageRoot)), '/');
        $prefix = (string) config('filesystems.x_accel_prefix', env('X_ACCEL_PREFIX', '/internal-storage/'));

        return rtrim($prefix, '/').'/'.$relative;
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
        if ($file->is_pending || $file->is_failed) {
            if ($file->path) {
                Storage::disk('local')->delete($file->path);
            }
        } elseif ($file->is_telegram) {
            $this->telegram->deleteMessage($file);
        } else {
            Storage::disk('local')->delete($file->path);
        }

        $file->delete();
    }

    private function safeDownloadName(string $name): string
    {
        $name = trim(str_replace(["\r", "\n"], '', $name));

        return $name !== '' ? $name : 'file';
    }

    private function asciiDownloadFallback(string $name): string
    {
        $fallback = preg_replace('/[^A-Za-z0-9._-]+/', '_', $this->safeDownloadName($name)) ?: 'file';

        return trim($fallback, '._') !== '' ? $fallback : 'file';
    }
}
