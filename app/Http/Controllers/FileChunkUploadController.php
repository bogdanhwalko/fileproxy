<?php

namespace App\Http\Controllers;

use App\Models\TelegramStorageGroup;
use App\Services\ManagedFileStorageService;
use App\Services\ProtectedFileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Throwable;

/**
 * Receives a large upload in 5 MB chunks from the browser so that no single
 * HTTP request stays open for minutes (which times out on shared hosting).
 *
 * Flow:
 *   1. Client splits File via Blob.slice() into ordered chunks
 *   2. Each chunk POSTed here with shared upload_id + index
 *   3. Server appends bytes to storage/app/upload-sessions/{user}/{uploadId}.part
 *   4. On the last chunk (finalize=1), server hands the assembled file off to
 *      the normal upload pipeline (storeAssembledFile → dispatch job)
 */
class FileChunkUploadController extends Controller
{
    private const CHUNK_SIZE_LIMIT_KB = 8 * 1024;       // 8 MB hard cap per chunk POST (client uses 5 MB)

    private const PROTECTED_TOTAL_LIMIT = 500 * 1024 * 1024; // 500 MB for protected files

    private const TOTAL_CHUNKS_LIMIT = 200;             // 200 × 5 MB = 1 GB upper safety bound

    public function uploadChunk(Request $request, ManagedFileStorageService $fileStorage): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'upload_id'                 => ['required', 'string', 'size:32', 'regex:/^[A-Za-z0-9]+$/'],
            'chunk_index'               => ['required', 'integer', 'min:0'],
            'total_chunks'              => ['required', 'integer', 'min:1', 'max:'.self::TOTAL_CHUNKS_LIMIT],
            'total_size'                => ['required', 'integer', 'min:1'],
            'original_name'             => ['required', 'string', 'max:255'],
            'mime_type'                 => ['nullable', 'string', 'max:127'],
            'folder_id'                 => ['nullable', 'integer', Rule::exists('file_folders', 'id')->where('user_id', $user->id)],
            'telegram_storage_group_id' => ['nullable', 'integer', Rule::exists('telegram_storage_groups', 'id')->where('user_id', $user->id)],
            'is_protected'              => ['nullable', 'boolean'],
            'finalize'                  => ['nullable', 'boolean'],
            'chunk'                     => ['required', 'file', 'max:'.self::CHUNK_SIZE_LIMIT_KB],
        ]);

        $isProtected = (bool) ($validated['is_protected'] ?? false);
        $finalize    = (bool) ($validated['finalize'] ?? false);
        $uploadId    = $validated['upload_id'];
        $totalSize   = (int) $validated['total_size'];

        $maxBytes = $isProtected ? self::PROTECTED_TOTAL_LIMIT : ProtectedFileService::CHUNK_SIZE_BYTES * 2;

        if ($totalSize > $maxBytes) {
            return response()->json([
                'message' => 'Файл занадто великий: '.(int) ($totalSize / 1024 / 1024)
                    .' МБ > '.(int) ($maxBytes / 1024 / 1024).' МБ',
            ], 422);
        }

        if ($isProtected && empty($validated['telegram_storage_group_id'])) {
            return response()->json([
                'message' => 'Захищені файли потребують вибраної своєї Telegram-групи.',
            ], 422);
        }

        $sessionDir = storage_path('app/upload-sessions/'.$user->id);
        if (! is_dir($sessionDir) && ! @mkdir($sessionDir, 0775, true) && ! is_dir($sessionDir)) {
            return response()->json(['message' => 'Не вдалося створити теку сесій.'], 500);
        }

        $partPath = $sessionDir.'/'.$uploadId.'.part';
        $metaPath = $sessionDir.'/'.$uploadId.'.meta.json';

        $meta = $this->loadOrInitMeta($metaPath, $user->id, $validated, $isProtected);

        // Reject params drift between requests (anti-tamper)
        if (
            (int) $meta['total_size'] !== $totalSize
            || (int) $meta['total_chunks'] !== (int) $validated['total_chunks']
            || $meta['original_name'] !== $validated['original_name']
        ) {
            return response()->json(['message' => 'Сесія не співпадає з параметрами запиту.'], 422);
        }

        // Strict sequential ordering — keeps file assembly deterministic
        if ((int) $validated['chunk_index'] !== $meta['chunks_received']) {
            return response()->json([
                'message' => 'Очікую chunk #'.$meta['chunks_received'].', отримано #'.$validated['chunk_index'].'.',
                'expected_chunk_index' => $meta['chunks_received'],
            ], 409);
        }

        $chunkFile = $request->file('chunk');
        $chunkBytes = @file_get_contents($chunkFile->getRealPath());
        if ($chunkBytes === false) {
            return response()->json(['message' => 'Не вдалося прочитати завантажений chunk.'], 500);
        }

        $written = @file_put_contents($partPath, $chunkBytes, FILE_APPEND);
        if ($written === false) {
            return response()->json(['message' => 'Не вдалося записати chunk у файл сесії.'], 500);
        }

        $meta['bytes_received'] += strlen($chunkBytes);
        $meta['chunks_received']++;

        if ($meta['bytes_received'] > $meta['total_size']) {
            @unlink($partPath);
            @unlink($metaPath);

            return response()->json([
                'message' => 'Отримано більше байтів, ніж заявлено у total_size.',
            ], 422);
        }

        @file_put_contents($metaPath, json_encode($meta, JSON_UNESCAPED_UNICODE));

        if (! $finalize) {
            return response()->json([
                'message'         => 'chunk received',
                'bytes_received'  => $meta['bytes_received'],
                'chunks_received' => $meta['chunks_received'],
            ]);
        }

        // === FINALIZE ===
        if ($meta['chunks_received'] !== $meta['total_chunks']) {
            return response()->json([
                'message' => 'Finalize call отримано, але прийнято '.$meta['chunks_received'].'/'.$meta['total_chunks'].' chunks.',
            ], 422);
        }
        if ($meta['bytes_received'] !== $meta['total_size']) {
            return response()->json([
                'message' => 'Розбіжність байтів: очікую '.$meta['total_size'].', отримано '.$meta['bytes_received'].'.',
            ], 422);
        }

        $telegramGroup = null;
        if (! empty($meta['telegram_storage_group_id'])) {
            $telegramGroup = TelegramStorageGroup::query()
                ->where('user_id', $user->id)
                ->find((int) $meta['telegram_storage_group_id']);

            if (! $telegramGroup) {
                $this->cleanupSession($partPath, $metaPath);

                return response()->json(['message' => 'Обрана Telegram-група недоступна.'], 422);
            }
        }

        if ($isProtected && ! $telegramGroup) {
            $this->cleanupSession($partPath, $metaPath);

            return response()->json(['message' => 'Захищені файли потребують Telegram-групи.'], 422);
        }

        try {
            $file = $fileStorage->storeAssembledFile(
                user: $user,
                sourcePath: $partPath,
                originalName: $meta['original_name'],
                mimeType: $meta['mime_type'] ?: null,
                folderId: $meta['folder_id'] ?: null,
                telegramGroup: $telegramGroup,
                isProtected: $isProtected,
            );
        } catch (Throwable $exception) {
            report($exception);
            $this->cleanupSession($partPath, $metaPath);

            return response()->json([
                'message' => 'Не вдалося завершити завантаження: '.$exception->getMessage(),
            ], 500);
        }

        // storeAssembledFile moves the .part file; meta.json still here
        @unlink($metaPath);

        return response()->json([
            'message' => 'OK',
            'files'   => [[
                'id'                    => $file->id,
                'original_name'         => $file->original_name,
                'status'                => $file->status,
                'size'                  => (int) $file->size,
                'storage_driver'        => $file->storage_driver,
                'upload_failure_reason' => $file->upload_failure_reason,
            ]],
        ], 201);
    }

    private function loadOrInitMeta(string $metaPath, int $userId, array $validated, bool $isProtected): array
    {
        if (is_file($metaPath)) {
            $raw = @file_get_contents($metaPath);
            $decoded = $raw === false ? null : json_decode($raw, true);
            if (is_array($decoded) && (int) ($decoded['user_id'] ?? 0) === $userId) {
                return $decoded;
            }
        }

        return [
            'user_id'                    => $userId,
            'original_name'              => $validated['original_name'],
            'mime_type'                  => $validated['mime_type'] ?? null,
            'total_size'                 => (int) $validated['total_size'],
            'total_chunks'               => (int) $validated['total_chunks'],
            'bytes_received'             => 0,
            'chunks_received'            => 0,
            'folder_id'                  => $validated['folder_id'] ?? null,
            'telegram_storage_group_id'  => $validated['telegram_storage_group_id'] ?? null,
            'is_protected'               => $isProtected,
            'created_at'                 => time(),
        ];
    }

    private function cleanupSession(string $partPath, string $metaPath): void
    {
        @unlink($partPath);
        @unlink($metaPath);
    }
}
