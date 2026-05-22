<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ManagedFileResource;
use App\Models\ManagedFile;
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
use Illuminate\Validation\Rule;
use RuntimeException;
use Throwable;

class FileController extends Controller
{
    private const TELEGRAM_UPLOAD_MAX_KB = 51200;

    private const SYSTEM_TELEGRAM_UPLOAD_LIMIT = 100;

    private const UPLOAD_FILES_PER_REQUEST_LIMIT = 25;

    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();
        $perPage = (int) $request->integer('per_page', 20);
        $perPage = max(1, min(100, $perPage));

        $query = $user->files()
            ->with(['folder', 'telegramStorageGroup.botToken']);

        if ($request->filled('folder_id')) {
            $folderId = $request->input('folder_id');

            if ($folderId === 'root' || $folderId === 'null') {
                $query->whereNull('folder_id');
            } elseif (ctype_digit((string) $folderId)) {
                $query->where('folder_id', (int) $folderId);
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

        $file->load(['folder', 'telegramStorageGroup.botToken']);

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

    private function ensureOwner(?User $user, ManagedFile $file): void
    {
        abort_unless($user && (int) $file->user_id === (int) $user->id, 404);
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
