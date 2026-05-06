<?php

namespace App\Http\Controllers;

use App\Models\ManagedFile;
use App\Models\TelegramStorageGroup;
use App\Models\User;
use App\Services\ManagedFileStorageService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
    private const TELEGRAM_UPLOAD_MAX_KB = 51200;

    private const SYSTEM_TELEGRAM_UPLOAD_LIMIT = 100;

    private const ARCHIVE_FILE_LIMIT = 500;

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
            : 'table';
        $imagePreviews = $display === 'grid' && $request->boolean('image_previews');
        $folderFilter = (string) $request->query('folder', 'all');
        $dateFrom = trim((string) $request->query('date_from', ''));
        $dateTo = trim((string) $request->query('date_to', ''));
        [$dateFromCarbon, $dateToCarbon] = $this->parseDateRange($dateFrom, $dateTo);
        $user = $request->user();
        $activeFolder = null;

        $baseQuery = $user->files()->with(['folder', 'telegramStorageGroup.botToken']);

        if ($folderFilter === 'root') {
            $baseQuery->whereNull('folder_id');
        } elseif ($folderFilter !== 'all' && $folderFilter !== '') {
            abort_unless(ctype_digit($folderFilter), 404);

            $activeFolder = $user->folders()->findOrFail((int) $folderFilter);
            $baseQuery->where('folder_id', $activeFolder->id);
        } else {
            $folderFilter = 'all';
        }

        $applyContentFilters = function ($query) use ($search, $type, $dateFromCarbon, $dateToCarbon) {
            return $query
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($query) use ($search) {
                        $query
                            ->where('original_name', 'like', "%{$search}%")
                            ->orWhere('mime_type', 'like', "%{$search}%")
                            ->orWhere('extension', 'like', "%{$search}%");
                    });
                })
                ->when($type !== 'all', fn ($query) => $this->applyTypeFilter($query, $type))
                ->when($dateFromCarbon !== null, fn ($query) => $query->where('created_at', '>=', $dateFromCarbon))
                ->when($dateToCarbon !== null, fn ($query) => $query->where('created_at', '<=', $dateToCarbon));
        };

        $files = $applyContentFilters(clone $baseQuery)
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $filteredCount = $applyContentFilters(clone $baseQuery)->count();

        $folders = $user->folders()
            ->withCount('files')
            ->orderBy('name')
            ->get();

        $telegramStorageGroups = $this->telegramStorageGroups($user);
        $systemTelegramStorageGroups = $this->systemTelegramStorageGroups();
        $systemTelegramUsedUploads = $this->systemTelegramUploadCount($user, $systemTelegramStorageGroups);
        $systemTelegramRemainingUploads = max(0, self::SYSTEM_TELEGRAM_UPLOAD_LIMIT - $systemTelegramUsedUploads);

        $stats = [
            'total' => $user->files()->count(),
            'storage' => ManagedFile::formatBytes((int) $user->files()->sum('size')),
            'folders' => $folders->count(),
            'telegram' => $user->files()->where('storage_driver', 'telegram')->count(),
            'current' => (clone $baseQuery)->count(),
            'root' => $user->files()->whereNull('folder_id')->count(),
        ];

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
            'imagePreviews' => $imagePreviews,
            'telegramUploadMaxMb' => (int) (self::TELEGRAM_UPLOAD_MAX_KB / 1024),
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

    public function store(Request $request, ManagedFileStorageService $fileStorage): RedirectResponse
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
            'files' => ['required', 'array', 'min:1'],
            'files.*' => ['file', 'max:'.self::TELEGRAM_UPLOAD_MAX_KB],
        ], [
            'files.required' => 'Оберіть хоча б один файл для завантаження.',
            'files.*.max' => 'Максимальний розмір одного файлу для Telegram Bot API - 50 MB.',
            'folder_id.exists' => 'Обрана папка недоступна.',
            'telegram_storage_group_id.exists' => 'Обрана Telegram-група недоступна.',
        ]);

        $folderId = $validated['folder_id'] ?? null;
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
                return back()
                    ->withInput($request->except('files'))
                    ->withErrors(['telegram_storage_group_id' => 'Звичайні користувачі можуть завантажувати файли тільки в Telegram-групу.']);
            }

            $systemTelegramStorageGroups = $this->systemTelegramStorageGroups();

            if ($systemTelegramStorageGroups->isEmpty()) {
                return back()
                    ->withInput($request->except('files'))
                    ->withErrors(['telegram_storage_group_id' => 'Адміністратор ще не налаштував системну Telegram-групу для завантажень.']);
            }

            $systemTelegramUsedUploads = $this->systemTelegramUploadCount($user, $systemTelegramStorageGroups);
            $systemTelegramRemainingUploads = self::SYSTEM_TELEGRAM_UPLOAD_LIMIT - $systemTelegramUsedUploads;
            $requestedUploads = count($validated['files']);

            if ($systemTelegramRemainingUploads <= 0) {
                return back()
                    ->withInput($request->except('files'))
                    ->withErrors(['telegram_storage_group_id' => 'Ви вже використали системний ліміт 100 файлів. Підключіть власну Telegram-групу.']);
            }

            if ($requestedUploads > $systemTelegramRemainingUploads) {
                return back()
                    ->withInput($request->except('files'))
                    ->withErrors(['files' => "Системне Telegram-сховище дозволяє завантажити ще {$systemTelegramRemainingUploads} файлів."]);
            }

            $useSystemTelegramStorage = true;
        }

        try {
            foreach ($validated['files'] as $index => $uploadedFile) {
                $targetTelegramGroup = $telegramGroup;

                if ($useSystemTelegramStorage) {
                    $targetTelegramGroup = $systemTelegramStorageGroups
                        ->values()
                        ->get(($systemTelegramUsedUploads + $index) % $systemTelegramStorageGroups->count());
                }

                $fileStorage->storeUploadedFile($user, $uploadedFile, $folderId, $targetTelegramGroup);
            }
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withInput($request->except('files'))
                ->withErrors(['files' => $exception instanceof RuntimeException
                    ? $exception->getMessage()
                    : 'Не вдалося завантажити файли. Перевірте сховище та повторіть спробу.']);
        }

        $count = count($validated['files']);
        $storageLabel = match (true) {
            (bool) $telegramGroup => ' у Telegram-групу "'.$telegramGroup->title.'"',
            $useSystemTelegramStorage => ' у системне Telegram-сховище',
            default => '',
        };
        $routeParameters = $folderId ? ['folder' => $folderId] : [];

        return redirect()
            ->route('files.index', $routeParameters)
            ->with('status', "Завантажено файлів{$storageLabel}: {$count}.");
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
        abort_unless($file->is_image, 404);
        abort_unless($fileStorage->exists($file), 404);

        return $fileStorage->inlineResponse($file);
    }

    public function destroy(ManagedFile $file, ManagedFileStorageService $fileStorage): RedirectResponse
    {
        abort_unless((int) $file->user_id === (int) auth()->id(), 404);

        $routeParameters = $file->folder_id ? ['folder' => $file->folder_id] : [];

        $fileStorage->delete($file);

        return redirect()
            ->route('files.index', $routeParameters)
            ->with('status', 'Файл видалено.');
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
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query
                        ->where('original_name', 'like', "%{$search}%")
                        ->orWhere('mime_type', 'like', "%{$search}%")
                        ->orWhere('extension', 'like', "%{$search}%");
                });
            })
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

        return response()
            ->download($zipPath, $downloadName, ['Content-Type' => 'application/zip'])
            ->deleteFileAfterSend();
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
            ->whereIn('telegram_storage_group_id', $groups->pluck('id'))
            ->count();
    }

    private function schemaProblems(): array
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
