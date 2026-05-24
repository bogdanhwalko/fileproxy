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
    public function __construct(
        private readonly TelegramFileStorageService $telegram,
        private readonly ProtectedFileService $protected,
    ) {}

    /**
     * @param  array<int,int>  $tagIds  IDs of tags to attach after creation
     */
    public function storeUploadedFile(
        User $user,
        UploadedFile $uploadedFile,
        ?int $folderId = null,
        ?TelegramStorageGroup $telegramGroup = null,
        bool $isProtected = false,
        array $tagIds = []
    ): ManagedFile {
        $extension = preg_replace('/[^a-z0-9]+/', '', strtolower($uploadedFile->getClientOriginalExtension())) ?? '';
        $storedName = (string) Str::uuid();

        if ($extension !== '') {
            $storedName .= ".{$extension}";
        }

        if ($telegramGroup) {
            $pendingDirectory = 'uploads-pending/'.$user->id;
            $pendingPath = $uploadedFile->storeAs($pendingDirectory, $storedName, 'local');

            $size = $uploadedFile->getSize() ?: 0;

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
                'size' => $size,
                'status' => ManagedFile::STATUS_PENDING,
                'is_protected' => $isProtected,
                'original_size' => $isProtected ? $size : null,
                'chunk_count' => $isProtected ? (int) ceil($size / ProtectedFileService::CHUNK_SIZE_BYTES) : null,
            ]);

            if ($tagIds !== []) {
                $file->tags()->sync($tagIds);
            }

            UploadManagedFileToTelegram::dispatch($file);

            return $file;
        }

        $storageDirectory = 'uploads/'.$user->id.'/'.($folderId ? "folders/{$folderId}" : 'root');
        $path = $uploadedFile->storeAs($storageDirectory, $storedName, 'local');

        $file = ManagedFile::create([
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

        if ($tagIds !== []) {
            $file->tags()->sync($tagIds);
        }

        return $file;
    }

    /**
     * Create a ManagedFile record from a pre-assembled binary file on disk.
     * Used by chunked-upload flow (FileChunkUploadController) where the final
     * file has been built by appending many small HTTP chunks.
     *
     * The source file is moved into uploads-pending/{user_id}/{uuid}.{ext}.
     *
     * @param  array<int,int>  $tagIds  IDs of tags to attach after creation
     */
    public function storeAssembledFile(
        User $user,
        string $sourcePath,
        string $originalName,
        ?string $mimeType,
        ?int $folderId,
        ?TelegramStorageGroup $telegramGroup,
        bool $isProtected = false,
        array $tagIds = []
    ): ManagedFile {
        $rawExt = pathinfo($originalName, PATHINFO_EXTENSION);
        $extension = preg_replace('/[^a-z0-9]+/', '', strtolower((string) $rawExt)) ?: '';
        $storedName = (string) Str::uuid();
        if ($extension !== '') {
            $storedName .= ".{$extension}";
        }
        $size = (int) (@filesize($sourcePath) ?: 0);

        if ($telegramGroup) {
            $pendingDirectory = 'uploads-pending/'.$user->id;
            Storage::disk('local')->makeDirectory($pendingDirectory);
            $pendingPath = $pendingDirectory.'/'.$storedName;
            $absDest = Storage::disk('local')->path($pendingPath);

            if (! @rename($sourcePath, $absDest)) {
                if (! @copy($sourcePath, $absDest)) {
                    throw new \RuntimeException('Could not move assembled chunked file to uploads-pending.');
                }
                @unlink($sourcePath);
            }

            $file = ManagedFile::create([
                'user_id' => $user->id,
                'folder_id' => $folderId,
                'storage_driver' => 'telegram',
                'telegram_bot_token_id' => $telegramGroup->telegram_bot_token_id,
                'telegram_storage_group_id' => $telegramGroup->id,
                'original_name' => $originalName,
                'stored_name' => $storedName,
                'path' => $pendingPath,
                'mime_type' => $mimeType ?: 'application/octet-stream',
                'extension' => $extension ?: null,
                'size' => $size,
                'status' => ManagedFile::STATUS_PENDING,
                'is_protected' => $isProtected,
                'original_size' => $isProtected ? $size : null,
                'chunk_count' => $isProtected ? (int) ceil($size / ProtectedFileService::CHUNK_SIZE_BYTES) : null,
            ]);

            if ($tagIds !== []) {
                $file->tags()->sync($tagIds);
            }

            UploadManagedFileToTelegram::dispatch($file);

            return $file;
        }

        $storageDirectory = 'uploads/'.$user->id.'/'.($folderId ? "folders/{$folderId}" : 'root');
        Storage::disk('local')->makeDirectory($storageDirectory);
        $path = $storageDirectory.'/'.$storedName;
        $absDest = Storage::disk('local')->path($path);

        if (! @rename($sourcePath, $absDest)) {
            if (! @copy($sourcePath, $absDest)) {
                throw new \RuntimeException('Could not move assembled chunked file to local storage.');
            }
            @unlink($sourcePath);
        }

        $file = ManagedFile::create([
            'user_id' => $user->id,
            'folder_id' => $folderId,
            'storage_driver' => 'local',
            'original_name' => $originalName,
            'stored_name' => $storedName,
            'path' => $path,
            'mime_type' => $mimeType ?: 'application/octet-stream',
            'extension' => $extension ?: null,
            'size' => $size,
        ]);

        if ($tagIds !== []) {
            $file->tags()->sync($tagIds);
        }

        return $file;
    }


    public function exists(ManagedFile $file): bool
    {
        if ($file->is_pending || $file->is_failed) {
            return false;
        }

        if ($file->is_protected) {
            return $file->chunks()->count() === (int) $file->chunk_count && $file->chunk_count > 0;
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

        if ($file->is_protected) {
            return $this->protectedStreamResponse($file, HeaderUtils::DISPOSITION_ATTACHMENT, $downloadHeaders);
        }

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

    private function protectedStreamResponse(ManagedFile $file, string $disposition, array $extraHeaders = []): StreamedResponse
    {
        $headers = $extraHeaders + [
            'Content-Disposition' => HeaderUtils::makeDisposition(
                $disposition,
                $this->safeDownloadName($file->original_name),
                $this->asciiDownloadFallback($file->original_name)
            ),
        ];

        if ($file->original_size) {
            $headers['Content-Length'] = (string) $file->original_size;
        }

        return response()->stream(function () use ($file): void {
            @ignore_user_abort(false);

            try {
                foreach ($this->protected->streamDecrypted($file) as $plaintext) {
                    if ($plaintext === '') {
                        continue;
                    }
                    echo $plaintext;

                    if (ob_get_level() > 0) {
                        @ob_flush();
                    }
                    flush();

                    if (connection_aborted()) {
                        break;
                    }
                }
            } catch (\Throwable $e) {
                report($e);
                // Stream may already have started; nothing useful to do but bail
            }
        }, 200, $headers);
    }

    public function inlineResponse(ManagedFile $file): StreamedResponse|BinaryFileResponse|Response
    {
        $mime = $file->mime_type ?: 'application/octet-stream';

        // Defense in depth: if the file's MIME isn't on the safe-inline whitelist
        // (HTML, scripts, etc.), force the browser to download instead of render.
        // CSP sandbox already blocks scripts, but this prevents social-engineering
        // via a "shared image" link that opens an HTML phishing page.
        if (! $this->isSafeInlineMime($mime)) {
            return $this->downloadResponse($file);
        }

        if ($file->is_protected) {
            return $this->protectedStreamResponse($file, HeaderUtils::DISPOSITION_INLINE, $this->inlineSecurityHeaders($mime));
        }

        $inlineHeaders = $this->inlineSecurityHeaders($mime);

        if (! $file->is_telegram) {
            if ($accel = $this->xAccelResponse($file, HeaderUtils::DISPOSITION_INLINE)) {
                return $accel;
            }

            return Storage::disk('local')->response($file->path, $file->original_name, $inlineHeaders);
        }

        $stream = $this->telegram->downloadStream($file);

        return response()->stream(function () use ($stream): void {
            @ignore_user_abort(false);

            try {
                while (! $stream->eof()) {
                    echo $stream->read(1024 * 1024);

                    if (ob_get_level() > 0) {
                        @ob_flush();
                    }

                    flush();

                    if (connection_aborted()) {
                        break;
                    }
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

    /**
     * Whitelist of MIME types that are safe to render inline.
     * Anything else (HTML, JS, SVG, executables, etc.) is forced to attachment.
     */
    private function isSafeInlineMime(string $mime): bool
    {
        $mime = strtolower(trim($mime));

        if ($mime === '') {
            return false;
        }

        // SVG can contain scripts even with sandbox in some legacy contexts — never inline.
        if (str_contains($mime, 'svg')) {
            return false;
        }

        $safePrefixes = ['image/', 'audio/', 'video/'];

        foreach ($safePrefixes as $prefix) {
            if (str_starts_with($mime, $prefix)) {
                return true;
            }
        }

        $safeExact = [
            'application/pdf',
            'text/plain',
            'text/csv',
            'text/markdown',
        ];

        return in_array($mime, $safeExact, true);
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
        if ($file->is_protected) {
            // Remove all encrypted chunks from Telegram (best-effort)
            $this->protected->deleteChunks($file);

            // For pending protected files, the unencrypted source may still be in uploads-pending
            if (str_starts_with((string) $file->path, 'uploads-pending/')) {
                Storage::disk('local')->delete($file->path);
            }
        } elseif ($file->is_pending || $file->is_failed) {
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
