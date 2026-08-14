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
use RuntimeException;
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
            // Protected files have no Range/seek support when streamed live (each
            // request would re-fetch and re-decrypt every chunk from Telegram from
            // scratch), so inline preview only works once warmProtectedPreviewCache()
            // has decrypted the file to a local temp cache — see preload().
            abort_unless($this->hasFreshProtectedPreviewCache($file), 404);

            return Storage::disk('local')->response(
                $this->protectedPreviewCachePath($file),
                $file->original_name,
                $this->inlineSecurityHeaders($mime)
            );
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

    /**
     * For temporary generated files only (e.g. archive zips) — deliberately
     * always streams through PHP instead of offering X-Accel-Redirect like
     * xAccelResponse() does for permanent managed files. Under X-Accel, nginx
     * serves the file directly and finishes long after this method returns,
     * with no signal back to PHP for when it's actually done — deleting the
     * file from here would risk unlinking it while nginx is still streaming
     * it out, truncating the client's download. deleteFileAfterSend() is only
     * safe because PHP itself is the one holding the file open until sent.
     */
    public function downloadLocalPathResponse(string $absolutePath, string $downloadName, string $contentType = 'application/zip'): BinaryFileResponse
    {
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

    /**
     * Read the full file content as a string (capped at the editor limit).
     * Used by the in-browser text editor when loading an existing file for
     * editing. Returns [content, was_truncated].
     *
     * @return array{0:string,1:bool}
     */
    public function readFullText(ManagedFile $file, int $maxBytes = 5_242_880): array
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

        // Read maxBytes + 1 so we can detect truncation
        $content = stream_get_contents($stream, $maxBytes + 1);
        fclose($stream);

        if ($temporaryPath) {
            @unlink($temporaryPath);
        }

        abort_unless($content !== false, 404);

        $isTruncated = strlen($content) > $maxBytes;
        if ($isTruncated) {
            $content = substr($content, 0, $maxBytes);
        }

        return [$content, $isTruncated];
    }

    /**
     * Replace the contents of an existing file with the given text. For local
     * files we just overwrite the path. For telegram files we delete the
     * existing message and re-upload via the standard job (returning a new
     * ManagedFile that should be saved over the old one).
     *
     * Keeps folder_id, tags, share_token intact. Caller is responsible for
     * blocking protected files (no re-encryption supported here yet).
     */
    public function overwriteText(ManagedFile $file, string $content, ?string $newOriginalName = null, ?string $newMime = null, ?string $newExtension = null): ManagedFile
    {
        $newSize = strlen($content);

        // Local files: overwrite the stored blob in place, update metadata.
        if (! $file->is_telegram) {
            Storage::disk('local')->put($file->path, $content);

            $update = [
                'size' => $newSize,
            ];
            if ($newOriginalName !== null) $update['original_name'] = $newOriginalName;
            if ($newMime !== null)         $update['mime_type'] = $newMime;
            if ($newExtension !== null)    $update['extension'] = $newExtension;
            $file->update($update);

            return $file->fresh();
        }

        // Telegram-stored: send a new message with new bytes, swap message_id +
        // file_id, then delete the old message. Best-effort cleanup so we don't
        // leave two copies in the channel if anything fails.
        $tmpPath = tempnam(sys_get_temp_dir(), 'fp-edit-');
        if ($tmpPath === false) {
            throw new \RuntimeException('Не вдалося створити тимчасовий файл для оновлення.');
        }
        file_put_contents($tmpPath, $content);

        try {
            $group = $file->telegramStorageGroup;
            if (! $group) {
                throw new \RuntimeException('У файла немає привʼязаної Telegram-групи.');
            }

            $oldMessageId = $file->telegram_message_id;
            $oldChatId = (string) $file->telegram_chat_id;
            $oldBot = $file->telegramBotToken;

            // Upload new bytes to the same group
            $sent = $this->telegram->sendDocumentFromPath(
                $tmpPath,
                $newOriginalName ?? $file->original_name,
                $group,
                $newSize,
                $newMime ?? $file->mime_type ?? 'text/plain'
            );

            $update = [
                'telegram_chat_id'    => $sent['chat_id'],
                'telegram_message_id' => $sent['message_id'],
                'telegram_file_id'    => $sent['file_id'],
                'telegram_file_unique_id' => $sent['file_unique_id'] ?? $file->telegram_file_unique_id,
                'size'                => $sent['file_size'] ?? $newSize,
                'mime_type'           => $sent['mime_type'] ?? ($newMime ?? $file->mime_type),
            ];
            if ($newOriginalName !== null) $update['original_name'] = $newOriginalName;
            if ($newExtension !== null)    $update['extension'] = $newExtension;
            $file->update($update);

            // Best-effort delete of the old message
            if ($oldBot && $oldMessageId && $oldChatId !== '') {
                try { $this->telegram->deleteMessageRaw($oldBot, $oldChatId, (int) $oldMessageId); }
                catch (\Throwable) { /* old message orphaned, file still works */ }
            }
        } finally {
            if (file_exists($tmpPath)) @unlink($tmpPath);
        }

        return $file->fresh();
    }

    public function readTextPreview(ManagedFile $file): array
    {
        $temporaryPath = null;

        if ($file->is_protected) {
            // Never read $file->path directly for protected files — it's a
            // synthetic non-existent path; the real (decrypted) content only
            // exists in the preview cache once warmProtectedPreviewCache() ran.
            abort_unless($this->hasFreshProtectedPreviewCache($file), 404);

            $stream = Storage::disk('local')->readStream($this->protectedPreviewCachePath($file));
        } elseif ($file->is_telegram) {
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
        if ($file->is_protected) {
            return [$this->decryptToTemporaryPath($file), true];
        }

        if ($file->is_telegram) {
            return [$this->telegram->downloadToTemporaryPath($file), true];
        }

        return [Storage::disk('local')->path($file->path), false];
    }

    private function decryptToTemporaryPath(ManagedFile $file): string
    {
        $directory = 'telegram-temp/'.$file->user_id;
        Storage::disk('local')->makeDirectory($directory);

        $storagePath = $directory.'/'.Str::uuid().'_'.($file->stored_name ?: 'protected-file');
        $absolutePath = Storage::disk('local')->path($storagePath);

        try {
            $this->decryptToFile($file, $absolutePath);
        } catch (\Throwable $e) {
            @unlink($absolutePath);

            throw $e;
        }

        return $absolutePath;
    }

    private function decryptToFile(ManagedFile $file, string $absolutePath): void
    {
        $handle = fopen($absolutePath, 'wb');

        if ($handle === false) {
            throw new RuntimeException("Could not open destination file for protected file {$file->id}.");
        }

        try {
            foreach ($this->protected->streamDecrypted($file) as $plaintext) {
                fwrite($handle, $plaintext);
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * How long a decrypted protected-file preview cache stays usable after
     * warmProtectedPreviewCache() runs. Public so App\Console\Kernel's cleanup
     * schedule (which sweeps this directory far more often than the general
     * hourly job, since it holds plaintext on disk) can key off the same value
     * instead of duplicating the number.
     */
    public const PROTECTED_PREVIEW_CACHE_TTL = 1800;

    private function protectedPreviewCachePath(ManagedFile $file): string
    {
        return 'protected-preview-cache/'.$file->user_id.'/'.$file->id.'.bin';
    }

    public function hasFreshProtectedPreviewCache(ManagedFile $file): bool
    {
        $path = $this->protectedPreviewCachePath($file);
        $disk = Storage::disk('local');

        if (! $disk->exists($path)) {
            return false;
        }

        $lastModified = $disk->lastModified($path);

        return $lastModified !== false && $lastModified >= time() - self::PROTECTED_PREVIEW_CACHE_TTL;
    }

    /**
     * Decrypt a protected file's chunks (from Telegram) to a local cache file
     * so inline preview can serve it with normal Range/seek support instead of
     * re-fetching and re-decrypting every chunk on every request. Triggered
     * explicitly by the user via the "preload" action — never automatically —
     * since it re-downloads the whole file from Telegram each time it's warmed.
     */
    public function warmProtectedPreviewCache(ManagedFile $file): void
    {
        if (! $file->is_protected) {
            throw new RuntimeException("File {$file->id} is not protected.");
        }

        $finalPath = $this->protectedPreviewCachePath($file);
        $disk = Storage::disk('local');
        $disk->makeDirectory(dirname($finalPath));

        $absoluteFinalPath = $disk->path($finalPath);
        $absoluteTempPath = $absoluteFinalPath.'.'.Str::uuid().'.tmp';

        try {
            $this->decryptToFile($file, $absoluteTempPath);
        } catch (\Throwable $e) {
            @unlink($absoluteTempPath);

            throw $e;
        }

        // Atomic rename: concurrent inline() reads never see a partially-written file.
        rename($absoluteTempPath, $absoluteFinalPath);
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
