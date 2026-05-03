<?php

namespace App\Http\Controllers;

use App\Models\ManagedFile;
use App\Models\TelegramStorageGroup;
use App\Models\User;
use App\Services\ManagedFileStorageService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class FileController extends Controller
{
    private const TELEGRAM_UPLOAD_MAX_KB = 51200;

    private const SYSTEM_TELEGRAM_UPLOAD_LIMIT = 100;

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $type = (string) $request->query('type', 'all');
        $display = in_array($request->query('view'), ['table', 'grid'], true)
            ? (string) $request->query('view')
            : 'table';
        $folderFilter = (string) $request->query('folder', 'all');
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

        $files = (clone $baseQuery)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query
                        ->where('original_name', 'like', "%{$search}%")
                        ->orWhere('mime_type', 'like', "%{$search}%")
                        ->orWhere('extension', 'like', "%{$search}%");
                });
            })
            ->when($type !== 'all', fn ($query) => $this->applyTypeFilter($query, $type))
            ->latest()
            ->paginate(20)
            ->withQueryString();

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
            'display' => $display,
            'files' => $files,
            'folderFilter' => $folderFilter,
            'folders' => $folders,
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
                ->withErrors(['files' => $exception->getMessage()]);
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

    private function applyTypeFilter($query, string $type)
    {
        return match ($type) {
            'images' => $query->where('mime_type', 'like', 'image/%'),
            'documents' => $query->where(function ($query) {
                $query
                    ->where('mime_type', 'like', 'text/%')
                    ->orWhereIn('extension', ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv']);
            }),
            'archives' => $query->whereIn('extension', ['zip', 'rar', '7z', 'tar', 'gz']),
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
}
