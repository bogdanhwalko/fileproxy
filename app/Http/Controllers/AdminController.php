<?php

namespace App\Http\Controllers;

use App\Models\ManagedFile;
use App\Models\User;
use App\Services\ManagedFileStorageService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class AdminController extends Controller
{
    public function users(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', 'all');

        $query = User::query()
            ->withCount(['files', 'telegramBotTokens', 'telegramStorageGroups'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($status === 'blocked', fn ($query) => $query->where('is_blocked', true))
            ->when($status === 'active', fn ($query) => $query->where('is_blocked', false));

        $users = $query
            ->orderByDesc('is_admin')
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('admin.users', [
            'search' => $search,
            'stats' => [
                'users' => User::count(),
                'blocked' => User::where('is_blocked', true)->count(),
                'admins' => User::where('is_admin', true)->count(),
                'files' => ManagedFile::count(),
            ],
            'status' => $status,
            'users' => $users,
        ]);
    }

    public function showUser(Request $request, User $user): View
    {
        $search = trim((string) $request->query('search', ''));
        $type = (string) $request->query('type', 'all');
        $display = in_array($request->query('view'), ['table', 'grid'], true)
            ? (string) $request->query('view')
            : 'grid';
        $imagePreviews = $display === 'grid' && $request->boolean('image_previews', false);

        // per_page driven by client-side density toggle (cookie fp_per_page):
        // comfortable=12, compact=18, list=30. Validate strictly.
        $perPage = (int) ($request->cookie('fp_per_page', 12));
        if (! in_array($perPage, [12, 18, 30], true)) {
            $perPage = 12;
        }
        $folderFilter = (string) $request->query('folder', 'all');
        $dateFrom = trim((string) $request->query('date_from', ''));
        $dateTo = trim((string) $request->query('date_to', ''));
        [$dateFromCarbon, $dateToCarbon] = $this->parseDateRange($dateFrom, $dateTo);
        $activeFolder = null;

        $user->loadCount(['files', 'folders', 'telegramBotTokens', 'telegramStorageGroups']);

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
            ->paginate($perPage)
            ->withQueryString();

        $filteredCount = $applyContentFilters(clone $baseQuery)->count();

        $folders = $user->folders()
            ->withCount('files')
            ->orderBy('name')
            ->get();

        $aggregates = $user->files()
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw('COALESCE(SUM(size), 0) AS storage_bytes')
            ->selectRaw("SUM(CASE WHEN storage_driver = 'telegram' THEN 1 ELSE 0 END) AS telegram")
            ->selectRaw('SUM(CASE WHEN folder_id IS NULL THEN 1 ELSE 0 END) AS root')
            ->first();

        return view('admin.user', [
            'activeFolder' => $activeFolder,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'display' => $display,
            'files' => $files,
            'filteredCount' => $filteredCount,
            'folderFilter' => $folderFilter,
            'folders' => $folders,
            'imagePreviews' => $imagePreviews,
            'search' => $search,
            'stats' => [
                'total' => (int) ($aggregates->total ?? 0),
                'storage' => ManagedFile::formatBytes((int) ($aggregates->storage_bytes ?? 0)),
                'folders' => $folders->count(),
                'telegram' => (int) ($aggregates->telegram ?? 0),
                'current' => (clone $baseQuery)->count(),
                'root' => (int) ($aggregates->root ?? 0),
            ],
            'type' => $type,
            'user' => $user,
        ]);
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

    public function downloadFile(User $user, ManagedFile $file, ManagedFileStorageService $fileStorage)
    {
        $this->ensureUserFile($user, $file);
        abort_unless($fileStorage->exists($file), 404);

        return $fileStorage->downloadResponse($file);
    }

    public function previewFile(User $user, ManagedFile $file, ManagedFileStorageService $fileStorage)
    {
        $this->ensureUserFile($user, $file);
        abort_unless($fileStorage->exists($file), 404);
        abort_unless($file->is_previewable, 404);

        $content = null;
        $isTruncated = false;

        if ($file->is_text) {
            [$content, $isTruncated] = $fileStorage->readTextPreview($file);
        }

        return view('admin.file-preview', [
            'content' => $content,
            'file' => $file,
            'isTruncated' => $isTruncated,
            'user' => $user,
        ]);
    }

    public function inlineFile(User $user, ManagedFile $file, ManagedFileStorageService $fileStorage)
    {
        $this->ensureUserFile($user, $file);
        abort_unless($file->is_image, 404);
        abort_unless($fileStorage->exists($file), 404);

        return $fileStorage->inlineResponse($file);
    }

    public function destroyFile(User $user, ManagedFile $file, ManagedFileStorageService $fileStorage): RedirectResponse
    {
        $this->ensureUserFile($user, $file);

        $fileStorage->delete($file);

        return back()->with('status', 'Файл користувача видалено.');
    }

    public function blockUser(User $user): RedirectResponse
    {
        abort_if((int) $user->id === (int) auth()->id(), 422, 'Не можна заблокувати власний акаунт.');

        $user->forceFill(['is_blocked' => true])->save();

        return back()->with('status', 'Користувача заблоковано.');
    }

    public function unblockUser(User $user): RedirectResponse
    {
        $user->forceFill(['is_blocked' => false])->save();

        return back()->with('status', 'Користувача розблоковано.');
    }

    private function ensureUserFile(User $user, ManagedFile $file): void
    {
        abort_unless((int) $file->user_id === (int) $user->id, 404);
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
}
