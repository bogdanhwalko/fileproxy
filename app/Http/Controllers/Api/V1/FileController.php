<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\FiltersManagedFiles;
use App\Http\Controllers\Controller;
use App\Http\Resources\ManagedFileResource;
use App\Models\ManagedFile;
use App\Models\Tag;
use App\Models\TelegramStorageGroup;
use App\Models\User;
use App\Services\ManagedFileStorageService;
use Carbon\Carbon;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;
use ZipArchive;

class FileController extends Controller
{
    use FiltersManagedFiles;

    private const TELEGRAM_UPLOAD_MAX_KB = 51200;

    private const SYSTEM_TELEGRAM_UPLOAD_LIMIT = 100;

    private const UPLOAD_FILES_PER_REQUEST_LIMIT = 25;

    private const ARCHIVE_FILE_LIMIT = 500;

    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();
        $perPage = (int) $request->integer('per_page', 20);
        $perPage = max(1, min(100, $perPage));

        $query = $user->files()
            ->with(['folder', 'telegramStorageGroup.botToken', 'tags']);

        // Protected files, and files in a password-protected folder, are
        // excluded from folder-agnostic listing here just like the web "All
        // files" view — unless the caller asked for that exact protected
        // folder by id, in which case they get its real contents (the owner's
        // bearer token already proves access; there's no session-unlock
        // concept in a stateless API).
        $protectedFolderIds = $user->folders()->whereNotNull('password_hash')->pluck('id')->all();
        $targetingSpecificProtectedFolder = false;

        if ($request->filled('folder_id')) {
            $folderId = $request->input('folder_id');

            if ($folderId === 'root' || $folderId === 'null') {
                $query->whereNull('folder_id');
            } elseif (ctype_digit((string) $folderId)) {
                $query->where('folder_id', (int) $folderId);
                $targetingSpecificProtectedFolder = in_array((int) $folderId, $protectedFolderIds, true);
            }
        }

        if (! $targetingSpecificProtectedFolder) {
            $query->where('is_protected', false);

            if (! empty($protectedFolderIds)) {
                $query->where(function ($q) use ($protectedFolderIds) {
                    $q->whereNull('folder_id')->orWhereNotIn('folder_id', $protectedFolderIds);
                });
            }
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->query('search'));
            $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $search).'%';
            $query->where(function ($q) use ($like, $search) {
                $q->where('original_name', 'like', $like)
                    ->orWhere('mime_type', 'like', $like)
                    ->orWhere('extension', 'like', '%'.strtolower($search).'%');
            });
        }

        if ($request->filled('storage_driver')) {
            $driver = (string) $request->query('storage_driver');

            if (in_array($driver, ['local', 'telegram'], true)) {
                $query->where('storage_driver', $driver);
            }
        }

        if ($request->filled('date_from')) {
            try {
                $query->where('created_at', '>=', Carbon::parse((string) $request->query('date_from'))->startOfDay());
            } catch (Throwable) {
            }
        }

        if ($request->filled('date_to')) {
            try {
                $query->where('created_at', '<=', Carbon::parse((string) $request->query('date_to'))->endOfDay());
            } catch (Throwable) {
            }
        }

        $files = $query->latest()->paginate($perPage)->withQueryString();

        return ManagedFileResource::collection($files);
    }

    public function show(Request $request, ManagedFile $file): ManagedFileResource
    {
        $this->ensureOwner($request->user(), $file);

        $file->load(['folder', 'telegramStorageGroup.botToken', 'tags']);

        return new ManagedFileResource($file);
    }

    public function store(Request $request, ManagedFileStorageService $fileStorage): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'folder_id' => [
                'nullable',
                'integer',
                Rule::exists('file_folders', 'id')->where('user_id', $user->id),
            ],
            'telegram_storage_group_id' => [
                'nullable',
                'integer',
                Rule::exists('telegram_storage_groups', 'id')->where('user_id', $user->id),
            ],
            'files' => ['required_without:file', 'array', 'min:1', 'max:'.self::UPLOAD_FILES_PER_REQUEST_LIMIT],
            'files.*' => ['file', 'max:'.self::TELEGRAM_UPLOAD_MAX_KB],
            'file' => ['required_without:files', 'file', 'max:'.self::TELEGRAM_UPLOAD_MAX_KB],
        ]);

        $uploadedFiles = $request->file('files') ?? [$request->file('file')];
        $uploadedFiles = array_values(array_filter($uploadedFiles));

        if ($uploadedFiles === []) {
            return response()->json(['message' => 'No file was provided.'], 422);
        }

        $folderId = $validated['folder_id'] ?? null;
        $telegramStorageGroups = $user->telegramStorageGroups()->with('botToken')->get();
        $telegramGroup = null;
        $systemGroups = new Collection;
        $useSystemTelegram = false;
        $systemUsed = 0;

        if (! empty($validated['telegram_storage_group_id'])) {
            $telegramGroup = $telegramStorageGroups->firstWhere('id', (int) $validated['telegram_storage_group_id']);

            if (! $telegramGroup) {
                return response()->json(['message' => 'Telegram storage group is not available.'], 404);
            }
        }

        if (! $user->is_admin && ! $telegramGroup) {
            if ($telegramStorageGroups->isNotEmpty()) {
                return response()->json([
                    'message' => 'Select telegram_storage_group_id from your own groups.',
                ], 422);
            }

            $systemGroups = TelegramStorageGroup::query()
                ->with('botToken')
                ->where('is_global_default', true)
                ->orderBy('id')
                ->get();

            if ($systemGroups->isEmpty()) {
                return response()->json([
                    'message' => 'System Telegram storage is not configured. Connect your own bot and group.',
                ], 422);
            }

            $useSystemTelegram = true;
        }

        $lock = $useSystemTelegram
            ? Cache::lock('fileproxy:system-tg-upload:'.$user->id, 10)
            : null;

        $created = [];

        try {
            $lock?->block(5);

            if ($useSystemTelegram) {
                $systemUsed = $this->systemTelegramUploadCount($user, $systemGroups);
                $remaining = self::SYSTEM_TELEGRAM_UPLOAD_LIMIT - $systemUsed;
                $requested = count($uploadedFiles);

                if ($remaining <= 0) {
                    return response()->json([
                        'message' => 'System upload quota of 100 files has been exhausted. Connect your own Telegram group.',
                    ], 403);
                }

                if ($requested > $remaining) {
                    return response()->json([
                        'message' => "System Telegram storage allows uploading {$remaining} more file(s).",
                    ], 403);
                }
            }

            foreach ($uploadedFiles as $index => $uploadedFile) {
                $target = $telegramGroup;

                if ($useSystemTelegram) {
                    $target = $systemGroups
                        ->values()
                        ->get(($systemUsed + $index) % $systemGroups->count());
                }

                $created[] = $fileStorage->storeUploadedFile($user, $uploadedFile, $folderId, $target);
            }
        } catch (LockTimeoutException) {
            return response()->json([
                'message' => 'Another upload from this account is in progress. Retry in a few seconds.',
            ], 409);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => $exception instanceof RuntimeException
                    ? $exception->getMessage()
                    : 'Failed to upload files.',
            ], 500);
        } finally {
            $lock?->release();
        }

        return ManagedFileResource::collection(collect($created)->load(['folder', 'telegramStorageGroup.botToken']))
            ->response()
            ->setStatusCode(201);
    }

    public function content(Request $request, ManagedFile $file, ManagedFileStorageService $fileStorage)
    {
        $this->ensureOwner($request->user(), $file);

        if (! $fileStorage->exists($file)) {
            return response()->json(['message' => 'File is not available for download.'], 404);
        }

        return $fileStorage->downloadResponse($file);
    }

    public function destroy(Request $request, ManagedFile $file, ManagedFileStorageService $fileStorage): JsonResponse
    {
        $this->ensureOwner($request->user(), $file);

        $fileStorage->delete($file);

        return response()->json(['message' => 'File deleted.']);
    }

    /**
     * Replace a file's tags. Accepts either an array of tag names or a
     * comma/semicolon/newline-separated string; always a full sync (replace),
     * mirroring the web UI's tag editor. Max 20 tags per request.
     */
    public function updateTags(Request $request, ManagedFile $file): ManagedFileResource
    {
        $this->ensureOwner($request->user(), $file);

        $tagIds = $this->resolveTagIds($request->input('tags'), $file->user_id);
        $file->tags()->sync($tagIds);
        $file->load(['folder', 'telegramStorageGroup.botToken', 'tags']);

        return new ManagedFileResource($file);
    }

    public function bulkDestroy(Request $request, ManagedFileStorageService $fileStorage): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:200'],
            'ids.*' => ['integer'],
        ]);

        $files = $user->files()->whereIn('id', $validated['ids'])
            ->with(['telegramBotToken', 'telegramStorageGroup.botToken'])
            ->get();

        $deleted = 0;
        $failed = 0;

        foreach ($files as $file) {
            try {
                $fileStorage->delete($file);
                $deleted++;
            } catch (Throwable $exception) {
                report($exception);
                $failed++;
            }
        }

        return response()->json([
            'message' => "Deleted {$deleted} file(s), {$failed} failed.",
            'deleted' => $deleted,
            'failed' => $failed,
        ]);
    }

    public function bulkMove(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:500'],
            'ids.*' => ['integer'],
            'folder_id' => ['nullable', 'integer', Rule::exists('file_folders', 'id')->where('user_id', $user->id)],
        ], [
            'folder_id.exists' => 'The selected folder is not available.',
        ]);

        $targetFolderId = $validated['folder_id'] ?? null;
        $moved = $user->files()->whereIn('id', $validated['ids'])->update(['folder_id' => $targetFolderId]);

        return response()->json([
            'message' => "Moved {$moved} file(s).",
            'moved' => (int) $moved,
            'folder_id' => $targetFolderId,
        ]);
    }

    /**
     * Download a zip of files matching the same filters as the file list
     * (search/type/folder/date range). Mirrors the web downloadArchive()
     * action — see FiltersManagedFiles for the shared query-filter logic.
     */
    public function archive(Request $request, ManagedFileStorageService $fileStorage): BinaryFileResponse
    {
        abort_unless(class_exists(ZipArchive::class), 503);

        $user = $request->user();

        $search = trim((string) $request->query('search', ''));
        $type = (string) $request->query('type', 'all');
        $folderFilter = (string) $request->query('folder', 'all');
        $dateFrom = trim((string) $request->query('date_from', ''));
        $dateTo = trim((string) $request->query('date_to', ''));
        [$dateFromCarbon, $dateToCarbon] = $this->parseDateRange($dateFrom, $dateTo);

        $query = $user->files()->with(['folder', 'telegramBotToken', 'telegramStorageGroup.botToken']);

        $folderName = null;

        if ($folderFilter === 'root') {
            $query->whereNull('folder_id');
            $folderName = 'root';
        } elseif ($folderFilter !== 'all' && $folderFilter !== '' && ctype_digit($folderFilter)) {
            $folder = $user->folders()->findOrFail((int) $folderFilter);
            $query->where('folder_id', $folder->id);
            $folderName = Str::slug($folder->name) ?: 'folder-'.$folder->id;
        } else {
            // "All files" archive: never bundle protected files or files from a
            // password-protected folder — mirrors the exclusion in index().
            $protectedFolderIds = $user->folders()->whereNotNull('password_hash')->pluck('id')->all();

            $query->where('is_protected', false);

            if (! empty($protectedFolderIds)) {
                $query->where(function ($q) use ($protectedFolderIds) {
                    $q->whereNull('folder_id')->orWhereNotIn('folder_id', $protectedFolderIds);
                });
            }
        }

        $query
            ->when($search !== '', fn ($query) => $this->applySearchFilter($query, $search))
            ->when($type !== 'all', fn ($query) => $this->applyTypeFilter($query, $type))
            ->when($dateFromCarbon !== null, fn ($query) => $query->where('created_at', '>=', $dateFromCarbon))
            ->when($dateToCarbon !== null, fn ($query) => $query->where('created_at', '<=', $dateToCarbon));

        $totalMatching = (clone $query)->count();

        abort_if($totalMatching === 0, 404, 'No files matched the current filters.');
        abort_if(
            $totalMatching > self::ARCHIVE_FILE_LIMIT,
            413,
            'Archive is limited to '.self::ARCHIVE_FILE_LIMIT." files. Narrow the filters (matched: {$totalMatching})."
        );

        $files = $query->oldest()->get();

        $directory = storage_path('app/file-archives');
        File::ensureDirectoryExists($directory);

        $zipPath = $directory.'/'.Str::uuid().'.zip';
        $zip = new ZipArchive;

        abort_unless($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true, 500);

        $addedFiles = 0;
        $usedNames = [];
        $temporaryPaths = [];

        try {
            foreach ($files as $file) {
                if (! $fileStorage->exists($file)) {
                    continue;
                }

                try {
                    [$sourcePath, $deleteAfter] = $fileStorage->temporaryPathForArchive($file);
                } catch (Throwable $exception) {
                    report($exception);

                    continue;
                }

                if ($zip->addFile($sourcePath, $this->uniqueArchiveName($usedNames, $file->original_name))) {
                    $addedFiles++;
                }

                if ($deleteAfter) {
                    $temporaryPaths[] = $sourcePath;
                }
            }

            if ($addedFiles === 0) {
                $zip->addFromString('README.txt', 'No available file could be added to the archive.');
            }
        } finally {
            $zip->close();

            foreach ($temporaryPaths as $temporaryPath) {
                @unlink($temporaryPath);
            }
        }

        $downloadName = 'fileproxy-'.($folderName ?: 'export').'-'.now()->format('Y-m-d').'.zip';

        return $fileStorage->downloadLocalPathResponse($zipPath, $downloadName);
    }

    private function ensureOwner(?User $user, ManagedFile $file): void
    {
        abort_unless($user && (int) $file->user_id === (int) $user->id, 404);
    }

    /**
     * @return array<int, int>
     */
    private function resolveTagIds(mixed $rawInput, int $userId): array
    {
        if ($rawInput === null || $rawInput === '') {
            return [];
        }

        if (is_array($rawInput)) {
            $rawInput = implode(',', array_filter(array_map('strval', $rawInput)));
        }

        $names = Tag::parseInput((string) $rawInput);

        if ($names === []) {
            return [];
        }

        if (count($names) > 20) {
            $names = array_slice($names, 0, 20);
        }

        $tagIds = [];

        foreach ($names as $name) {
            $tagIds[] = Tag::firstOrCreate(['user_id' => $userId, 'name' => $name])->id;
        }

        return $tagIds;
    }

    private function systemTelegramUploadCount(User $user, Collection $groups): int
    {
        if ($groups->isEmpty()) {
            return 0;
        }

        return (int) $user->files()
            ->where('storage_driver', 'telegram')
            ->where('status', '!=', ManagedFile::STATUS_FAILED)
            ->whereIn('telegram_storage_group_id', $groups->pluck('id'))
            ->count();
    }
}
