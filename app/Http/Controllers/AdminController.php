<?php

namespace App\Http\Controllers;

use App\Models\ManagedFile;
use App\Models\User;
use App\Services\ManagedFileStorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

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

    public function showUser(User $user): View
    {
        $search = trim((string) request()->query('search', ''));
        $type = (string) request()->query('type', 'all');
        $display = in_array(request()->query('view'), ['table', 'grid'], true)
            ? (string) request()->query('view')
            : 'table';
        $folderFilter = (string) request()->query('folder', 'all');
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

        return view('admin.user', [
            'activeFolder' => $activeFolder,
            'display' => $display,
            'files' => $files,
            'folderFilter' => $folderFilter,
            'folders' => $folders,
            'search' => $search,
            'stats' => [
                'total' => $user->files()->count(),
                'storage' => ManagedFile::formatBytes((int) $user->files()->sum('size')),
                'folders' => $folders->count(),
                'telegram' => $user->files()->where('storage_driver', 'telegram')->count(),
                'current' => (clone $baseQuery)->count(),
                'root' => $user->files()->whereNull('folder_id')->count(),
            ],
            'type' => $type,
            'user' => $user,
        ]);
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
}
