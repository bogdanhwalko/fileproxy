<?php

namespace App\Http\Controllers;

use App\Models\ManagedFile;
use App\Models\Tag;
use App\Models\TelegramStorageGroup;
use App\Models\User;
use App\Services\ManagedFileStorageService;
use Carbon\Carbon;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;
use ZipArchive;

class FileController extends Controller
{
    private const TELEGRAM_UPLOAD_MAX_KB_DEFAULT = 51200;

    private function telegramUploadMaxKb(): int
    {
        $mb = (int) env('TELEGRAM_BOT_MAX_FILE_MB', 50);

        return max(1, $mb) * 1024;
    }

    /** Per-file size cap for protected uploads (10× normal). */
    private function protectedUploadMaxKb(): int
    {
        return (int) (\App\Services\ProtectedFileService::MAX_FILE_BYTES / 1024);
    }

    private const SYSTEM_TELEGRAM_UPLOAD_LIMIT = 100;

    private const ARCHIVE_FILE_LIMIT = 500;

    private const UPLOAD_FILES_PER_REQUEST_LIMIT = 25;

    public function index(Request $request): View
    {
        $schemaProblems = $this->schemaProblems();

        if ($schemaProblems !== []) {
            return view('files.schema-warning', [
                'problems' => $schemaProblems,
            ]);
        }

        $search = trim((string) $request->query('search', ''));
        $type = (string) $request->query('type', 'all');
        $display = in_array($request->query('view'), ['table', 'grid'], true)
            ? (string) $request->query('view')
            : 'grid';
        $imagePreviews = $display === 'grid' && $request->boolean('image_previews', false);

        // per_page is driven by the density toggle (cookie set client-side):
        // comfortable=12, compact=18, list=30. Reduced from 24/48 to keep page
        // weight reasonable when previews are toggled on. Validate strictly.
        $perPage = (int) ($request->cookie('fp_per_page', 12));
        if (! in_array($perPage, [12, 18, 30], true)) {
            $perPage = 12;
        }
        $folderFilter = (string) $request->query('folder', 'all');
        $dateFrom = trim((string) $request->query('date_from', ''));
        $dateTo = trim((string) $request->query('date_to', ''));
        [$dateFromCarbon, $dateToCarbon] = $this->parseDateRange($dateFrom, $dateTo);
        $tagFilter = trim((string) $request->query('tag', ''));
        $user = $request->user();
        $activeFolder = null;
        $activeTag = null;

        $baseQuery = $user->files()->with(['folder', 'telegramStorageGroup.botToken', 'tags']);

        if ($tagFilter !== '') {
            $activeTag = Tag::where('user_id', $user->id)->where('name', $tagFilter)->first();
            if ($activeTag) {
                $baseQuery->whereHas('tags', fn ($q) => $q->where('tags.id', $activeTag->id));
            } else {
                // Unknown tag → empty result, not 404
                $baseQuery->whereRaw('1 = 0');
            }
        }

        if ($folderFilter === 'root') {
            $baseQuery->whereNull('folder_id');
        } elseif ($folderFilter !== 'all' && $folderFilter !== '') {
            abort_unless(ctype_digit($folderFilter), 404);

            $activeFolder = $user->folders()->findOrFail((int) $folderFilter);
            $baseQuery->where('folder_id', $activeFolder->id);
        } else {
            $folderFilter = 'all';
        }

        // Folder password gate: if the active folder is locked, redirect to
        // the unlock prompt instead of rendering files. Also hide files from
        // locked folders in any non-folder-specific listing (All files, Without
        // folder, search-across-everything, tag filters).
        $unlock = app(\App\Services\FolderUnlockService::class);
        if ($activeFolder && $activeFolder->is_password_protected && ! $unlock->isUnlocked($activeFolder)) {
            return redirect()->route('folders.unlock-prompt', ['folder' => $activeFolder->id]);
        }

        $lockedFolderIds = $user->folders()
            ->whereNotNull('password_hash')
            ->pluck('id')
            ->reject(fn ($id) => $unlock->isUnlocked((int) $id))
            ->values()
            ->all();

        if (! empty($lockedFolderIds) && (! $activeFolder || in_array($activeFolder->id, $lockedFolderIds, true) === false)) {
            $baseQuery->where(function ($q) use ($lockedFolderIds) {
                $q->whereNull('folder_id')
                    ->orWhereNotIn('folder_id', $lockedFolderIds);
            });
        }

        $applyContentFilters = function ($query) use ($search, $type, $dateFromCarbon, $dateToCarbon) {
            return $query
                ->when($search !== '', fn ($query) => $this->applySearchFilter($query, $search))
                ->when($type !== 'all', fn ($query) => $this->applyTypeFilter($query, $type))
                ->when($dateFromCarbon !== null, fn ($query) => $query->where('created_at', '>=', $dateFromCarbon))
                ->when($dateToCarbon !== null, fn ($query) => $query->where('created_at', '<=', $dateToCarbon));
        };

        $files = $applyContentFilters(clone $baseQuery)
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        $filteredCount = $applyContentFilters(clone $baseQuery)->count();

        $folders = $user->folders()
            ->withCount('files')
            ->orderBy('name')
            ->get();

        $tags = Tag::query()
            ->where('user_id', $user->id)
            ->withCount('files')
            ->orderBy('name')
            ->get();

        $telegramStorageGroups = $this->telegramStorageGroups($user);
        $systemTelegramStorageGroups = $this->systemTelegramStorageGroups();
        $systemTelegramUsedUploads = $this->systemTelegramUploadCount($user, $systemTelegramStorageGroups);
        $systemTelegramRemainingUploads = max(0, self::SYSTEM_TELEGRAM_UPLOAD_LIMIT - $systemTelegramUsedUploads);

        $aggregates = $user->files()
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw('COALESCE(SUM(size), 0) AS storage_bytes')
            ->selectRaw("SUM(CASE WHEN storage_driver = 'telegram' THEN 1 ELSE 0 END) AS telegram")
            ->selectRaw('SUM(CASE WHEN folder_id IS NULL THEN 1 ELSE 0 END) AS root')
            ->first();

        $stats = [
            'total' => (int) ($aggregates->total ?? 0),
            'storage' => ManagedFile::formatBytes((int) ($aggregates->storage_bytes ?? 0)),
            'folders' => $folders->count(),
            'telegram' => (int) ($aggregates->telegram ?? 0),
            'current' => (clone $baseQuery)->count(),
            'root' => (int) ($aggregates->root ?? 0),
        ];

        $unlockedFolderIds = array_keys($unlock->unlockedFolderIds());

        return view('files.index', [
            'activeFolder' => $activeFolder,
            'canUseLocalStorage' => (bool) $user->is_admin,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'display' => $display,
            'files' => $files,
            'filteredCount' => $filteredCount,
            'archiveFileLimit' => self::ARCHIVE_FILE_LIMIT,
            'folderFilter' => $folderFilter,
            'folders' => $folders,
            'unlockedFolderIds' => $unlockedFolderIds,
            'tags' => $tags,
            'activeTag' => $activeTag,
            'tagFilter' => $tagFilter,
            'imagePreviews' => $imagePreviews,
            'telegramUploadMaxMb' => (int) ($this->telegramUploadMaxKb() / 1024),
            'protectedUploadMaxMb' => (int) ($this->protectedUploadMaxKb() / 1024),
            'search' => $search,
            'stats' => $stats,
            'systemTelegramRemainingUploads' => $systemTelegramRemainingUploads,
            'systemTelegramStorageAvailable' => ! $user->is_admin
                && $telegramStorageGroups->isEmpty()
                && $systemTelegramStorageGroups->isNotEmpty()
                && $systemTelegramRemainingUploads > 0,
            'systemTelegramUploadLimit' => self::SYSTEM_TELEGRAM_UPLOAD_LIMIT,
            'telegramStorageGroups' => $telegramStorageGroups,
            'type' => $type,
        ]);
    }

    public function store(Request $request, ManagedFileStorageService $fileStorage): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        $wantsJson = $request->wantsJson() || $request->ajax();

        $isProtected = $request->boolean('is_protected');
        $maxKbPerFile = $isProtected ? $this->protectedUploadMaxKb() : $this->telegramUploadMaxKb();
        $tagIds = $this->resolveTagIds($request->input('tags'), $user->id);

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
            'files' => ['required', 'array', 'min:1', 'max:'.self::UPLOAD_FILES_PER_REQUEST_LIMIT],
            'files.*' => ['file', 'max:'.$maxKbPerFile],
            'is_protected' => ['nullable', 'boolean'],
            'tags' => ['nullable', 'string', 'max:1000'],
        ], [
            'files.required' => 'Оберіть хоча б один файл для завантаження.',
            'files.max' => 'За один раз можна завантажити не більше '.self::UPLOAD_FILES_PER_REQUEST_LIMIT.' файлів.',
            'files.*.max' => $isProtected
                ? 'Максимальний розмір одного захищеного файла — '.(int) ($maxKbPerFile / 1024).' MB.'
                : 'Максимальний розмір одного файлу для Telegram Bot API - '.(int) ($maxKbPerFile / 1024).' MB.',
            'folder_id.exists' => 'Обрана папка недоступна.',
            'telegram_storage_group_id.exists' => 'Обрана Telegram-група недоступна.',
        ]);

        // Protected files require Telegram storage (encryption + chunking has no meaning for local files)
        // and only work for users with their own bot/group (system storage has its own quotas/limits).
        if ($isProtected && ! $user->is_admin && empty($validated['telegram_storage_group_id'])) {
            return $this->uploadErrorResponse($request, $wantsJson,
                'is_protected',
                'Захищені файли потребують вибраної власної Telegram-групи.'
            );
        }

        $folderId = $validated['folder_id'] ?? null;

        // Auto-protect uploads to password-protected folders so they're stored
        // encrypted in Telegram regardless of the user's checkbox. This requires
        // a per-file size cap of the protected pipeline; revalidate sizes.
        if ($folderId !== null) {
            $targetFolder = $user->folders()->find($folderId);
            if ($targetFolder && $targetFolder->is_password_protected && ! $isProtected) {
                $isProtected = true;
                $maxKbPerFile = $this->protectedUploadMaxKb();
                foreach ($validated['files'] as $f) {
                    if ($f->getSize() / 1024 > $maxKbPerFile) {
                        return $this->uploadErrorResponse($request, $wantsJson,
                            'files',
                            'Папка захищена паролем — кожен файл шифрується. Максимум '.(int) ($maxKbPerFile / 1024).' MB на файл.'
                        );
                    }
                }
                // Protected files require user's own Telegram group
                if (! $user->is_admin && empty($validated['telegram_storage_group_id'])) {
                    return $this->uploadErrorResponse($request, $wantsJson,
                        'is_protected',
                        'Захищена папка вимагає завантаження у власну Telegram-групу.'
                    );
                }
            }
        }

        $telegramStorageGroups = $this->telegramStorageGroups($user);
        $telegramGroup = null;
        $systemTelegramStorageGroups = new Collection;
        $systemTelegramUsedUploads = 0;
        $useSystemTelegramStorage = false;

        if (! empty($validated['telegram_storage_group_id'])) {
            $telegramGroup = $telegramStorageGroups->firstWhere('id', (int) $validated['telegram_storage_group_id']);
            abort_unless($telegramGroup, 404);
        }

        if (! $user->is_admin && ! $telegramGroup) {
            if ($telegramStorageGroups->isNotEmpty()) {
                return $this->uploadErrorResponse($request, $wantsJson,
                    'telegram_storage_group_id',
                    'Звичайні користувачі можуть завантажувати файли тільки в Telegram-групу.'
                );
            }

            $systemTelegramStorageGroups = $this->systemTelegramStorageGroups();

            if ($systemTelegramStorageGroups->isEmpty()) {
                return $this->uploadErrorResponse($request, $wantsJson,
                    'telegram_storage_group_id',
                    'Адміністратор ще не налаштував системну Telegram-групу для завантажень.'
                );
            }

            $useSystemTelegramStorage = true;
        }

        $lock = $useSystemTelegramStorage
            ? Cache::lock('fileproxy:system-tg-upload:'.$user->id, 10)
            : null;

        $createdFiles = [];

        try {
            $lock?->block(5);

            if ($useSystemTelegramStorage) {
                $systemTelegramUsedUploads = $this->systemTelegramUploadCount($user, $systemTelegramStorageGroups);
                $systemTelegramRemainingUploads = self::SYSTEM_TELEGRAM_UPLOAD_LIMIT - $systemTelegramUsedUploads;
                $requestedUploads = count($validated['files']);

                if ($systemTelegramRemainingUploads <= 0) {
                    return $this->uploadErrorResponse($request, $wantsJson,
                        'telegram_storage_group_id',
                        'Ви вже використали системний ліміт 100 файлів. Підключіть власну Telegram-групу.'
                    );
                }

                if ($requestedUploads > $systemTelegramRemainingUploads) {
                    return $this->uploadErrorResponse($request, $wantsJson,
                        'files',
                        "Системне Telegram-сховище дозволяє завантажити ще {$systemTelegramRemainingUploads} файлів."
                    );
                }
            }

            foreach ($validated['files'] as $index => $uploadedFile) {
                $targetTelegramGroup = $telegramGroup;

                if ($useSystemTelegramStorage) {
                    $targetTelegramGroup = $systemTelegramStorageGroups
                        ->values()
                        ->get(($systemTelegramUsedUploads + $index) % $systemTelegramStorageGroups->count());
                }

                $createdFiles[] = $fileStorage->storeUploadedFile($user, $uploadedFile, $folderId, $targetTelegramGroup, $isProtected, $tagIds);
            }
        } catch (LockTimeoutException $exception) {
            return $this->uploadErrorResponse($request, $wantsJson,
                'files',
                'Уже триває інше завантаження від цього акаунта. Повторіть через кілька секунд.'
            );
        } catch (Throwable $exception) {
            report($exception);

            return $this->uploadErrorResponse($request, $wantsJson,
                'files',
                $exception instanceof RuntimeException
                    ? $exception->getMessage()
                    : 'Не вдалося завантажити файли. Перевірте сховище та повторіть спробу.'
            );
        } finally {
            $lock?->release();
        }

        $count = count($validated['files']);

        if ($wantsJson) {
            return response()->json([
                'message' => 'OK',
                'files' => array_map(fn (ManagedFile $f) => [
                    'id' => $f->id,
                    'original_name' => $f->original_name,
                    'status' => $f->status,
                    'size' => (int) $f->size,
                    'storage_driver' => $f->storage_driver,
                    'upload_failure_reason' => $f->upload_failure_reason,
                ], $createdFiles),
            ], 201);
        }

        $storageLabel = match (true) {
            (bool) $telegramGroup => ' у Telegram-групу "'.$telegramGroup->title.'"',
            $useSystemTelegramStorage => ' у системне Telegram-сховище',
            default => '',
        };
        $routeParameters = $folderId ? ['folder' => $folderId] : [];

        $useTelegram = (bool) $telegramGroup || $useSystemTelegramStorage;
        $statusMessage = $useTelegram
            ? "Поставлено в чергу обробки{$storageLabel}: {$count}. Файли зʼявляться в списку після завантаження в Telegram."
            : "Завантажено файлів{$storageLabel}: {$count}.";

        return redirect()
            ->route('files.index', $routeParameters)
            ->with('status', $statusMessage);
    }

    public function status(ManagedFile $file): JsonResponse
    {
        abort_unless((int) $file->user_id === (int) auth()->id(), 404);

        return response()->json([
            'id' => $file->id,
            'original_name' => $file->original_name,
            'status' => $file->status,
            'upload_failure_reason' => $file->upload_failure_reason,
        ]);
    }

    public function bulkDestroy(Request $request, ManagedFileStorageService $fileStorage): RedirectResponse|JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'ids'   => ['required', 'array', 'min:1', 'max:200'],
            'ids.*' => ['integer'],
        ]);

        $files = $user->files()
            ->whereIn('id', $validated['ids'])
            ->with(['telegramBotToken', 'telegramStorageGroup.botToken'])
            ->get();

        $deleted = 0;
        $failed  = 0;

        foreach ($files as $file) {
            try {
                $fileStorage->delete($file);
                $deleted++;
            } catch (Throwable $e) {
                report($e);
                $failed++;
            }
        }

        $message = $failed === 0
            ? "Видалено файлів: {$deleted}."
            : "Видалено: {$deleted}. Не вдалося видалити: {$failed}.";

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'message' => $message,
                'deleted' => $deleted,
                'failed'  => $failed,
            ]);
        }

        return redirect()
            ->to($this->safeReferer($request, route('files.index')))
            ->with('status', $message);
    }

    public function bulkMove(Request $request): RedirectResponse|JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'ids'       => ['required', 'array', 'min:1', 'max:500'],
            'ids.*'     => ['integer'],
            'folder_id' => [
                'nullable',
                'integer',
                Rule::exists('file_folders', 'id')->where('user_id', $user->id),
            ],
        ], [
            'folder_id.exists' => 'Обрана папка недоступна.',
        ]);

        $targetFolderId = $validated['folder_id'] ?? null;

        $moved = $user->files()
            ->whereIn('id', $validated['ids'])
            ->update(['folder_id' => $targetFolderId]);

        $folderName = $targetFolderId
            ? $user->folders()->where('id', $targetFolderId)->value('name')
            : null;

        $message = $folderName
            ? "Переміщено файлів у «{$folderName}»: {$moved}."
            : "Переміщено в корінь: {$moved}.";

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'message'   => $message,
                'moved'     => (int) $moved,
                'folder_id' => $targetFolderId,
            ]);
        }

        return redirect()
            ->to($this->safeReferer($request, route('files.index')))
            ->with('status', $message);
    }

    private function uploadErrorResponse(Request $request, bool $wantsJson, string $field, string $message): JsonResponse|RedirectResponse
    {
        if ($wantsJson) {
            return response()->json([
                'message' => $message,
                'errors' => [$field => [$message]],
            ], 422);
        }

        return back()
            ->withInput($request->except('files'))
            ->withErrors([$field => $message]);
    }

    public function download(ManagedFile $file, ManagedFileStorageService $fileStorage)
    {
        abort_unless((int) $file->user_id === (int) auth()->id(), 404);
        abort_unless($fileStorage->exists($file), 404);

        return $fileStorage->downloadResponse($file);
    }

    public function preview(ManagedFile $file, ManagedFileStorageService $fileStorage)
    {
        abort_unless((int) $file->user_id === (int) auth()->id(), 404);
        abort_unless($fileStorage->exists($file), 404);
        abort_unless($file->is_previewable, 404);
        abort_if($file->is_protected, 404); // protected files have no inline preview

        $content = null;
        $isTruncated = false;

        if ($file->is_text) {
            [$content, $isTruncated] = $fileStorage->readTextPreview($file);
        }

        return view('files.preview', [
            'content' => $content,
            'file' => $file,
            'isTruncated' => $isTruncated,
        ]);
    }

    public function inline(ManagedFile $file, ManagedFileStorageService $fileStorage)
    {
        abort_unless((int) $file->user_id === (int) auth()->id(), 404);
        // Allow inline serving for images AND PDFs (browser's native viewer
        // renders PDF inline via the application/pdf MIME). Other types stay
        // forbidden so we don't accidentally render HTML/SVG in the document.
        abort_unless($file->is_image || $file->is_pdf, 404);
        abort_if($file->is_protected, 404); // protected files never serve thumbnail/inline previews
        abort_unless($fileStorage->exists($file), 404);

        return $fileStorage->inlineResponse($file);
    }

    /**
     * Allowed extensions for the in-browser text editor. Limited to formats
     * that are safe to render inline and unlikely to host executable code.
     */
    private const TEXT_EDITOR_EXTENSIONS = [
        'txt', 'md', 'csv', 'log', 'json', 'yaml', 'yml',
        'ini', 'conf', 'env', 'xml', 'sql', 'tex',
    ];

    /**
     * Show the inline text-file editor (create-new form).
     */
    public function createText(Request $request): View
    {
        $user = $request->user();

        $folderId = $request->query('folder');
        $activeFolder = null;
        if ($folderId !== null && ctype_digit((string) $folderId)) {
            $activeFolder = $user->folders()->find((int) $folderId);
        }

        return view('files.text-editor', [
            'file' => null,
            'activeFolder' => $activeFolder,
            'folders' => $user->folders()->orderBy('name')->get(['id', 'name', 'color']),
            'telegramStorageGroups' => $this->telegramStorageGroups($user),
            'canUseLocalStorage' => (bool) $user->is_admin,
            'allowedExtensions' => self::TEXT_EDITOR_EXTENSIONS,
            'maxBytes' => 5 * 1024 * 1024, // 5 MB safety cap for text editor
        ]);
    }

    /**
     * Save the editor content as a new file.
     */
    public function storeText(Request $request, ManagedFileStorageService $fileStorage): RedirectResponse
    {
        $user = $request->user();
        $maxBytes = 5 * 1024 * 1024;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'content' => ['required', 'string', 'max:'.$maxBytes],
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
        ], [
            'name.required' => 'Введіть назву файла.',
            'content.required' => 'Файл порожній — додайте хоч щось.',
            'content.max' => 'Максимальний розмір текстового файла — 5 MB.',
            'folder_id.exists' => 'Папка недоступна.',
        ]);

        // Sanitize filename: strip path separators, normalize extension
        $rawName = trim($validated['name']);
        $rawName = preg_replace('#[\\\\/]+#', '_', $rawName) ?? 'untitled.txt';
        $rawName = preg_replace('/[\x00-\x1F]/', '', $rawName) ?? 'untitled.txt';
        if ($rawName === '') {
            $rawName = 'untitled.txt';
        }

        // Force a safe extension; default to .txt if missing/disallowed
        $extension = strtolower(pathinfo($rawName, PATHINFO_EXTENSION));
        if (! in_array($extension, self::TEXT_EDITOR_EXTENSIONS, true)) {
            $extension = 'txt';
            $rawName = pathinfo($rawName, PATHINFO_FILENAME).'.txt';
        }

        $mime = match ($extension) {
            'md'   => 'text/markdown',
            'csv'  => 'text/csv',
            'json' => 'application/json',
            'xml'  => 'application/xml',
            'html' => 'text/html',
            default => 'text/plain',
        };

        $telegramGroup = null;
        if (! empty($validated['telegram_storage_group_id'])) {
            $telegramGroup = $this->telegramStorageGroups($user)
                ->firstWhere('id', (int) $validated['telegram_storage_group_id']);
        }

        if (! $user->is_admin && ! $telegramGroup) {
            // Regular users must upload to Telegram. If no own group, use system.
            $systemGroups = $this->systemTelegramStorageGroups();
            if ($systemGroups->isNotEmpty()) {
                $telegramGroup = $systemGroups->first();
            }
        }

        // Block writing into a password-protected folder unless it's our own
        // protected pipeline (text editor doesn't currently support is_protected)
        $folderId = $validated['folder_id'] ?? null;
        if ($folderId !== null) {
            $folder = $user->folders()->find($folderId);
            if ($folder && $folder->is_password_protected) {
                return back()
                    ->withInput()
                    ->withErrors(['folder_id' => 'Текстовий редактор не підтримує запис у захищені папки.']);
            }
        }

        // Build a temporary UploadedFile from the editor content and reuse the
        // existing storeUploadedFile pipeline (handles local + telegram + tags).
        $tmpPath = tempnam(sys_get_temp_dir(), 'fp-text-');
        if ($tmpPath === false) {
            return back()->withInput()->withErrors(['content' => 'Не вдалося створити тимчасовий файл.']);
        }
        file_put_contents($tmpPath, $validated['content']);

        try {
            $uploaded = new \Illuminate\Http\UploadedFile(
                $tmpPath,
                $rawName,
                $mime,
                test: true // mark as test so it skips the is_uploaded_file() check on tmpnam
            );

            $file = $fileStorage->storeUploadedFile($user, $uploaded, $folderId, $telegramGroup, false, []);
        } finally {
            if (file_exists($tmpPath)) {
                @unlink($tmpPath);
            }
        }

        return redirect()
            ->route('files.index', $folderId ? ['folder' => $folderId] : [])
            ->with('status', 'Текстовий файл «'.$file->original_name.'» створено.');
    }

    public function destroy(Request $request, ManagedFile $file, ManagedFileStorageService $fileStorage): RedirectResponse
    {
        abort_unless((int) $file->user_id === (int) auth()->id(), 404);

        $fileStorage->delete($file);

        $fallback = route('files.index', $file->folder_id ? ['folder' => $file->folder_id] : []);

        return redirect()
            ->to($this->safeReferer($request, $fallback))
            ->with('status', 'Файл видалено.');
    }

    /**
     * Update tags for a single file (action menu → tag editor).
     */
    public function updateTags(Request $request, ManagedFile $file): JsonResponse|RedirectResponse
    {
        abort_unless((int) $file->user_id === (int) auth()->id(), 404);

        $tagIds = $this->resolveTagIds($request->input('tags'), $file->user_id);
        $file->tags()->sync($tagIds);

        $file->load('tags');

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'message' => 'Теги збережено.',
                'tags' => $file->tags->map(fn ($t) => ['id' => $t->id, 'name' => $t->name])->values(),
            ]);
        }

        return back()->with('status', 'Теги збережено.');
    }

    /**
     * Parse "tag1, tag2, ..." text into normalized tag IDs for $userId.
     * Creates missing tags. Limits to 20 tags per request (anti-abuse).
     *
     * @return array<int,int>
     */
    private function resolveTagIds(mixed $rawInput, int $userId): array
    {
        if ($rawInput === null || $rawInput === '') {
            return [];
        }

        // Allow either string ("a, b, c") or array (["a", "b", "c"])
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
            $tag = Tag::firstOrCreate(
                ['user_id' => $userId, 'name' => $name],
            );
            $tagIds[] = $tag->id;
        }

        return $tagIds;
    }

    private function safeReferer(Request $request, string $fallback): string
    {
        $referer = (string) $request->headers->get('referer', '');

        if ($referer === '') {
            return $fallback;
        }

        $refererHost = parse_url($referer, PHP_URL_HOST);
        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST) ?: $request->getHost();

        return $refererHost && $refererHost === $appHost ? $referer : $fallback;
    }

    public function downloadArchive(Request $request, ManagedFileStorageService $fileStorage): BinaryFileResponse
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
        }

        $query
            ->when($search !== '', fn ($query) => $this->applySearchFilter($query, $search))
            ->when($type !== 'all', fn ($query) => $this->applyTypeFilter($query, $type))
            ->when($dateFromCarbon !== null, fn ($query) => $query->where('created_at', '>=', $dateFromCarbon))
            ->when($dateToCarbon !== null, fn ($query) => $query->where('created_at', '<=', $dateToCarbon));

        $totalMatching = (clone $query)->count();

        abort_if($totalMatching === 0, 404, 'Жодного файлу не знайдено за поточними фільтрами.');
        abort_if(
            $totalMatching > self::ARCHIVE_FILE_LIMIT,
            413,
            "Архів обмежений до ".self::ARCHIVE_FILE_LIMIT." файлів. Уточніть фільтри (знайдено: {$totalMatching})."
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
                $zip->addFromString('README.txt', 'Жодного доступного файлу не знайдено для додавання в архів.');
            }
        } finally {
            $zip->close();

            foreach ($temporaryPaths as $temporaryPath) {
                @unlink($temporaryPath);
            }
        }

        $downloadName = 'fileproxy-'.($folderName ?: 'export').'-'.now()->format('Y-m-d').'.zip';

        $response = $fileStorage->downloadLocalPathResponse($zipPath, $downloadName);

        // Signal the client that the archive is ready by echoing back the
        // download_token in a short-lived cookie. The browser's archive overlay
        // polls for this cookie and hides itself once it sees a matching value.
        $token = (string) $request->query('download_token', '');
        if ($token !== '' && preg_match('/^[A-Za-z0-9_-]{6,64}$/', $token)) {
            $response->headers->setCookie(
                cookie('archive_ready', $token, 1, '/', null, false, false, false, 'Lax')
            );
        }

        return $response;
    }

    private function parseDateRange(string $from, string $to): array
    {
        $fromCarbon = null;
        $toCarbon = null;

        if ($from !== '') {
            try {
                $fromCarbon = Carbon::parse($from)->startOfDay();
            } catch (Throwable) {
                $fromCarbon = null;
            }
        }

        if ($to !== '') {
            try {
                $toCarbon = Carbon::parse($to)->endOfDay();
            } catch (Throwable) {
                $toCarbon = null;
            }
        }

        if ($fromCarbon && $toCarbon && $fromCarbon->greaterThan($toCarbon)) {
            [$fromCarbon, $toCarbon] = [$toCarbon->copy()->startOfDay(), $fromCarbon->copy()->endOfDay()];
        }

        return [$fromCarbon, $toCarbon];
    }

    private function uniqueArchiveName(array &$usedNames, string $name): string
    {
        $name = trim(str_replace(['/', '\\'], '-', $name));

        if ($name === '') {
            $name = 'file';
        }

        $baseName = pathinfo($name, PATHINFO_FILENAME) ?: 'file';
        $extension = pathinfo($name, PATHINFO_EXTENSION);
        $candidate = $name;
        $counter = 2;

        while (isset($usedNames[strtolower($candidate)])) {
            $candidate = $baseName.'-'.$counter.($extension ? '.'.$extension : '');
            $counter++;
        }

        $usedNames[strtolower($candidate)] = true;

        return $candidate;
    }

    private function applySearchFilter($query, string $search)
    {
        $search = trim($search);

        if ($search === '') {
            return $query;
        }

        $driver = $query->getModel()->getConnection()->getDriverName();
        $useFulltext = in_array($driver, ['mysql', 'mariadb'], true)
            && mb_strlen($search) >= 3
            && ! preg_match('/[%_]/', $search);

        if ($useFulltext) {
            $boolean = $this->buildFulltextBooleanQuery($search);
            $likeFallback = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $search).'%';

            return $query->where(function ($query) use ($boolean, $likeFallback, $search) {
                $query->whereRaw('MATCH(original_name) AGAINST (? IN BOOLEAN MODE)', [$boolean])
                    ->orWhere('original_name', 'like', $likeFallback)
                    ->orWhere('mime_type', 'like', '%'.$search.'%')
                    ->orWhere('extension', '=', strtolower($search));
            });
        }

        $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $search).'%';

        return $query->where(function ($query) use ($like, $search) {
            $query
                ->where('original_name', 'like', $like)
                ->orWhere('mime_type', 'like', $like)
                ->orWhere('extension', 'like', '%'.strtolower($search).'%');
        });
    }

    private function buildFulltextBooleanQuery(string $search): string
    {
        $tokens = preg_split('/\s+/u', $search) ?: [];
        $parts = [];

        foreach ($tokens as $token) {
            $token = preg_replace('/[+\-><()~*"@]+/u', '', $token);

            if ($token === null || mb_strlen($token) < 2) {
                continue;
            }

            $parts[] = '+'.$token.'*';
        }

        return $parts === [] ? $search : implode(' ', $parts);
    }

    private function applyTypeFilter($query, string $type)
    {
        return match ($type) {
            'images' => $query->where(function ($query) {
                $query
                    ->where('mime_type', 'like', 'image/%')
                    ->orWhereIn('extension', ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg', 'heic', 'heif', 'tiff', 'tif', 'ico', 'avif']);
            }),
            'videos' => $query->where(function ($query) {
                $query
                    ->where('mime_type', 'like', 'video/%')
                    ->orWhereIn('extension', ['mp4', 'mov', 'avi', 'mkv', 'webm', 'flv', 'wmv', 'm4v', '3gp', 'mpeg', 'mpg', 'ogv']);
            }),
            'audio' => $query->where(function ($query) {
                $query
                    ->where('mime_type', 'like', 'audio/%')
                    ->orWhereIn('extension', ['mp3', 'wav', 'ogg', 'oga', 'm4a', 'flac', 'aac', 'wma', 'opus', 'aiff', 'amr']);
            }),
            'documents' => $query->where(function ($query) {
                $query
                    ->where('mime_type', 'like', 'text/%')
                    ->orWhereIn('extension', ['pdf', 'doc', 'docx', 'odt', 'rtf', 'txt', 'md', 'tex', 'pages']);
            }),
            'spreadsheets' => $query->whereIn('extension', ['xls', 'xlsx', 'xlsm', 'ods', 'csv', 'tsv', 'numbers']),
            'presentations' => $query->whereIn('extension', ['ppt', 'pptx', 'odp', 'key']),
            'archives' => $query->whereIn('extension', ['zip', 'rar', '7z', 'tar', 'gz', 'bz2', 'xz', 'tgz', 'tbz', 'iso', 'dmg']),
            'code' => $query->whereIn('extension', ['js', 'ts', 'jsx', 'tsx', 'mjs', 'cjs', 'php', 'py', 'rb', 'go', 'rs', 'java', 'kt', 'swift', 'c', 'cpp', 'cc', 'h', 'hpp', 'cs', 'sh', 'bash', 'ps1', 'sql', 'html', 'htm', 'css', 'scss', 'sass', 'less', 'vue', 'svelte', 'json', 'yaml', 'yml', 'toml', 'ini', 'env', 'xml', 'lua', 'r', 'pl', 'dart']),
            'design' => $query->whereIn('extension', ['psd', 'ai', 'sketch', 'fig', 'xd', 'eps', 'indd', 'cdr']),
            'ebooks' => $query->whereIn('extension', ['epub', 'mobi', 'azw', 'azw3', 'fb2', 'djvu']),
            'fonts' => $query->whereIn('extension', ['ttf', 'otf', 'woff', 'woff2', 'eot']),
            default => $query,
        };
    }

    private function telegramStorageGroups(User $user): Collection
    {
        return $user->telegramStorageGroups()
            ->with('botToken')
            ->orderByDesc('is_default')
            ->orderBy('title')
            ->get();
    }

    private function systemTelegramStorageGroups(): Collection
    {
        return TelegramStorageGroup::query()
            ->with('botToken')
            ->where('is_global_default', true)
            ->orderBy('id')
            ->get();
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

    private function schemaProblems(): array
    {
        $cacheKey = 'fileproxy:schema-problems';

        if (Cache::get($cacheKey.':ok') === true) {
            return [];
        }

        $problems = $this->detectSchemaProblems();

        if ($problems === []) {
            Cache::put($cacheKey.':ok', true, now()->addMinutes(10));
        }

        return $problems;
    }

    private function detectSchemaProblems(): array
    {
        $requirements = [
                'managed_files' => [
                    'user_id',
                    'folder_id',
                    'storage_driver',
                    'telegram_storage_group_id',
                    'telegram_bot_token_id',
                    'telegram_chat_id',
                    'telegram_message_id',
                    'telegram_file_id',
                    'telegram_file_unique_id',
                    'status',
                    'upload_failure_reason',
                    'share_token',
                    'share_max_views',
                    'share_views_count',
                    'share_expires_at',
                ],
                'file_folders' => [
                    'user_id',
                    'share_token',
                    'share_max_views',
                    'share_views_count',
                    'share_expires_at',
                ],
                'telegram_bot_tokens' => [
                    'user_id',
                    'token',
                    'webhook_secret',
                ],
                'telegram_storage_groups' => [
                    'user_id',
                    'telegram_bot_token_id',
                    'chat_id',
                    'is_global_default',
                ],
        ];

        $problems = [];

        foreach ($requirements as $table => $columns) {
            if (! Schema::hasTable($table)) {
                $problems[] = "Відсутня таблиця {$table}.";

                continue;
            }

            foreach ($columns as $column) {
                if (! Schema::hasColumn($table, $column)) {
                    $problems[] = "У таблиці {$table} відсутня колонка {$column}.";
                }
            }
        }

        return $problems;
    }
}
