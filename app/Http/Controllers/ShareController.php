<?php

namespace App\Http\Controllers;

use App\Models\FileFolder;
use App\Models\ManagedFile;
use App\Services\ManagedFileStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\View\View;
use ZipArchive;

class ShareController extends Controller
{
    private const SHARE_FOLDER_ARCHIVE_FILE_LIMIT = 200;

    public function shareFile(Request $request, ManagedFile $file): RedirectResponse|JsonResponse
    {
        $this->authorizeFileOwner($file);

        if (! $file->share_token) {
            $file->forceFill([
                'share_token' => $this->uniqueToken(ManagedFile::class),
                'share_views_count' => 0,
            ])->save();
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Публічне посилання для файла створено.',
                'share' => $this->fileSharePayload($file->refresh()),
            ]);
        }

        return back()->with('status', 'Публічне посилання для файла створено.');
    }

    public function updateFileShareSettings(Request $request, ManagedFile $file): JsonResponse
    {
        $this->authorizeFileOwner($file);
        abort_unless($file->share_token, 422);

        $validated = $request->validate([
            'share_max_views' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'share_expires_at' => ['nullable', 'date', 'after:now'],
        ], [
            'share_max_views.min' => 'Ліміт переглядів має бути не менше 1.',
            'share_max_views.max' => 'Ліміт переглядів занадто великий.',
            'share_expires_at.after' => 'Дата завершення має бути в майбутньому.',
        ]);

        $file->forceFill([
            'share_max_views' => $validated['share_max_views'] ?? null,
            'share_expires_at' => ! empty($validated['share_expires_at'])
                ? Carbon::parse($validated['share_expires_at'])
                : null,
        ])->save();

        return response()->json([
            'message' => 'Налаштування публічного лінка збережено.',
            'share' => $this->fileSharePayload($file->refresh()),
        ]);
    }

    public function unshareFile(Request $request, ManagedFile $file): RedirectResponse|JsonResponse
    {
        $this->authorizeFileOwner($file);

        $file->forceFill([
            'share_token' => null,
            'share_max_views' => null,
            'share_views_count' => 0,
            'share_expires_at' => null,
        ])->save();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Публічний доступ до файла вимкнено.',
                'share' => $this->fileSharePayload($file->refresh()),
            ]);
        }

        return back()->with('status', 'Публічний доступ до файла вимкнено.');
    }

    public function shareFolder(Request $request, FileFolder $folder): RedirectResponse|JsonResponse
    {
        $this->authorizeFolderOwner($folder);

        if (! $folder->share_token) {
            $folder->forceFill([
                'share_token' => $this->uniqueToken(FileFolder::class),
                'share_views_count' => 0,
            ])->save();
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Публічне посилання для папки створено.',
                'share' => $this->folderSharePayload($folder->refresh()),
            ]);
        }

        return back()->with('status', 'Публічне посилання для папки створено.');
    }

    public function updateFolderShareSettings(Request $request, FileFolder $folder): JsonResponse
    {
        $this->authorizeFolderOwner($folder);
        abort_unless($folder->share_token, 422);

        $validated = $request->validate([
            'share_max_views' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'share_expires_at' => ['nullable', 'date', 'after:now'],
        ], [
            'share_max_views.min' => 'Ліміт переглядів має бути не менше 1.',
            'share_max_views.max' => 'Ліміт переглядів занадто великий.',
            'share_expires_at.after' => 'Дата завершення має бути в майбутньому.',
        ]);

        $folder->forceFill([
            'share_max_views' => $validated['share_max_views'] ?? null,
            'share_expires_at' => ! empty($validated['share_expires_at'])
                ? Carbon::parse($validated['share_expires_at'])
                : null,
        ])->save();

        return response()->json([
            'message' => 'Налаштування публічного лінка папки збережено.',
            'share' => $this->folderSharePayload($folder->refresh()),
        ]);
    }

    public function unshareFolder(Request $request, FileFolder $folder): RedirectResponse|JsonResponse
    {
        $this->authorizeFolderOwner($folder);

        $folder->forceFill([
            'share_token' => null,
            'share_max_views' => null,
            'share_views_count' => 0,
            'share_expires_at' => null,
        ])->save();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Публічний доступ до папки вимкнено.',
                'share' => $this->folderSharePayload($folder->refresh()),
            ]);
        }

        return back()->with('status', 'Публічний доступ до папки вимкнено.');
    }

    public function showFile(string $token, ManagedFileStorageService $fileStorage): View
    {
        $file = $this->sharedFile($token);

        abort_unless($fileStorage->exists($file), 404);

        if (! $file->is_image) {
            $this->consumeFileShareView($file);
        }

        return $this->fileView($file, $fileStorage, [
            'backLabel' => 'На головну',
            'backUrl' => route('home'),
            'contextLabel' => 'Публічний файл',
            'downloadUrl' => route('share.files.download', $file->share_token),
            'inlineUrl' => $file->is_image ? route('share.files.inline', $file->share_token) : null,
        ]);
    }

    public function inlineFile(string $token, ManagedFileStorageService $fileStorage)
    {
        $file = $this->sharedFile($token);

        abort_unless($file->is_image, 404);
        abort_unless($fileStorage->exists($file), 404);

        $this->consumeFileShareView($file);

        return $fileStorage->inlineResponse($file);
    }

    public function rawFile(string $token, ManagedFileStorageService $fileStorage)
    {
        $file = $this->sharedFile($token);

        abort_unless($fileStorage->exists($file), 404);

        $this->consumeFileShareView($file);

        return $fileStorage->inlineResponse($file);
    }

    public function downloadFile(string $token, ManagedFileStorageService $fileStorage)
    {
        $file = $this->sharedFile($token);

        abort_unless($fileStorage->exists($file), 404);

        $this->consumeFileShareView($file);

        return $fileStorage->downloadResponse($file);
    }

    public function showFolder(string $token): View
    {
        $folder = $this->sharedFolder($token);
        $this->consumeFolderShareView($folder);

        $files = $folder->files()
            ->with(['folder', 'telegramStorageGroup.botToken'])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('share.folder', [
            'files' => $files,
            'folder' => $folder,
        ]);
    }

    public function downloadFolder(string $token, ManagedFileStorageService $fileStorage)
    {
        $folder = $this->sharedFolder($token);

        abort_unless(class_exists(ZipArchive::class), 503);

        $totalFiles = $folder->files()->count();

        abort_if(
            $totalFiles > self::SHARE_FOLDER_ARCHIVE_FILE_LIMIT,
            413,
            'Архів папки обмежений до '.self::SHARE_FOLDER_ARCHIVE_FILE_LIMIT.' файлів. Власник папки має завантажити архів вручну.'
        );

        $this->consumeFolderShareView($folder);

        $directory = storage_path('app/share-zips');
        File::ensureDirectoryExists($directory);

        $zipPath = $directory.'/'.Str::uuid().'.zip';
        $zip = new ZipArchive;

        abort_unless($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true, 500);

        $addedFiles = 0;
        $temporaryPaths = [];
        $usedNames = [];

        try {
            $folder->files()
                ->with(['telegramBotToken', 'telegramStorageGroup.botToken'])
                ->oldest()
                ->limit(self::SHARE_FOLDER_ARCHIVE_FILE_LIMIT)
                ->get()
                ->each(function (ManagedFile $file) use ($fileStorage, $zip, &$addedFiles, &$temporaryPaths, &$usedNames): void {
                    if (! $fileStorage->exists($file)) {
                        return;
                    }

                    try {
                        [$sourcePath, $deleteAfter] = $fileStorage->temporaryPathForArchive($file);
                    } catch (\Throwable $exception) {
                        report($exception);

                        return;
                    }

                    if ($zip->addFile($sourcePath, $this->uniqueArchiveName($usedNames, $file->original_name))) {
                        $addedFiles++;
                    }

                    if ($deleteAfter) {
                        $temporaryPaths[] = $sourcePath;
                    }
                });

            if ($addedFiles === 0) {
                $zip->addFromString('README.txt', 'У цій папці немає доступних файлів.');
            }
        } finally {
            $zip->close();

            foreach ($temporaryPaths as $temporaryPath) {
                @unlink($temporaryPath);
            }
        }

        $downloadName = (Str::slug($folder->name) ?: 'folder-'.$folder->id).'.zip';

        return $fileStorage->downloadLocalPathResponse($zipPath, $downloadName);
    }

    public function showFolderFile(string $token, ManagedFile $file, ManagedFileStorageService $fileStorage): View
    {
        $folder = $this->sharedFolder($token);
        $this->ensureFileBelongsToFolder($folder, $file);

        abort_unless($fileStorage->exists($file), 404);
        abort_unless($file->is_previewable, 404);

        if (! $file->is_image) {
            $this->consumeFolderShareView($folder);
        }

        return $this->fileView($file, $fileStorage, [
            'backLabel' => 'До папки',
            'backUrl' => route('share.folders.show', $folder->share_token),
            'contextLabel' => 'Папка: '.$folder->name,
            'downloadUrl' => route('share.folders.files.download', [$folder->share_token, $file]),
            'inlineUrl' => $file->is_image ? route('share.folders.files.inline', [$folder->share_token, $file]) : null,
        ]);
    }

    public function inlineFolderFile(string $token, ManagedFile $file, ManagedFileStorageService $fileStorage)
    {
        $folder = $this->sharedFolder($token);
        $this->ensureFileBelongsToFolder($folder, $file);

        abort_unless($file->is_image, 404);
        abort_unless($fileStorage->exists($file), 404);

        $this->consumeFolderShareView($folder);

        return $fileStorage->inlineResponse($file);
    }

    public function rawFolderFile(string $token, ManagedFile $file, ManagedFileStorageService $fileStorage)
    {
        $folder = $this->sharedFolder($token);
        $this->ensureFileBelongsToFolder($folder, $file);

        abort_unless($fileStorage->exists($file), 404);

        $this->consumeFolderShareView($folder);

        return $fileStorage->inlineResponse($file);
    }

    public function downloadFolderFile(string $token, ManagedFile $file, ManagedFileStorageService $fileStorage)
    {
        $folder = $this->sharedFolder($token);
        $this->ensureFileBelongsToFolder($folder, $file);

        abort_unless($fileStorage->exists($file), 404);

        $this->consumeFolderShareView($folder);

        return $fileStorage->downloadResponse($file);
    }

    private function fileView(ManagedFile $file, ManagedFileStorageService $fileStorage, array $links): View
    {
        $content = null;
        $isTruncated = false;

        if ($file->is_text) {
            [$content, $isTruncated] = $fileStorage->readTextPreview($file);
        }

        return view('share.file', [
            'backLabel' => $links['backLabel'],
            'backUrl' => $links['backUrl'],
            'content' => $content,
            'contextLabel' => $links['contextLabel'],
            'downloadUrl' => $links['downloadUrl'],
            'file' => $file,
            'inlineUrl' => $links['inlineUrl'],
            'isTruncated' => $isTruncated,
        ]);
    }

    private function sharedFile(string $token): ManagedFile
    {
        $file = ManagedFile::query()
            ->with(['folder', 'telegramStorageGroup.botToken', 'user'])
            ->where('share_token', $token)
            ->firstOrFail();

        abort_if($file->user?->is_blocked, 404);
        abort_if($file->share_is_expired || $file->share_limit_reached, 404);

        return $file;
    }

    private function consumeFileShareView(ManagedFile $file): void
    {
        $updated = ManagedFile::query()
            ->whereKey($file->id)
            ->where(function ($query) {
                $query->whereNull('share_expires_at')
                    ->orWhere('share_expires_at', '>=', now());
            })
            ->where(function ($query) {
                $query->whereNull('share_max_views')
                    ->orWhereColumn('share_views_count', '<', 'share_max_views');
            })
            ->increment('share_views_count');

        abort_unless($updated === 1, 404);
        $file->refresh();
    }

    private function sharedFolder(string $token): FileFolder
    {
        $folder = FileFolder::query()
            ->with('user')
            ->where('share_token', $token)
            ->firstOrFail();

        abort_if($folder->user?->is_blocked, 404);
        abort_if($folder->share_is_expired || $folder->share_limit_reached, 404);

        return $folder;
    }

    private function consumeFolderShareView(FileFolder $folder): void
    {
        $updated = FileFolder::query()
            ->whereKey($folder->id)
            ->where(function ($query) {
                $query->whereNull('share_expires_at')
                    ->orWhere('share_expires_at', '>=', now());
            })
            ->where(function ($query) {
                $query->whereNull('share_max_views')
                    ->orWhereColumn('share_views_count', '<', 'share_max_views');
            })
            ->increment('share_views_count');

        abort_unless($updated === 1, 404);
        $folder->refresh();
    }

    private function ensureFileBelongsToFolder(FileFolder $folder, ManagedFile $file): void
    {
        abort_unless((int) $file->folder_id === (int) $folder->id, 404);
        abort_unless((int) $file->user_id === (int) $folder->user_id, 404);

        $file->loadMissing(['folder', 'telegramStorageGroup.botToken']);
    }

    private function authorizeFileOwner(ManagedFile $file): void
    {
        abort_unless((int) $file->user_id === (int) auth()->id(), 404);
    }

    private function authorizeFolderOwner(FileFolder $folder): void
    {
        abort_unless((int) $folder->user_id === (int) auth()->id(), 404);
    }

    private function uniqueToken(string $modelClass): string
    {
        do {
            $token = Str::random(48);
        } while ($modelClass::query()->where('share_token', $token)->exists());

        return $token;
    }

    private function fileSharePayload(ManagedFile $file): array
    {
        $isEnabled = (bool) $file->share_token;
        $expiresAt = $file->share_expires_at;
        $viewsLabel = $file->share_max_views
            ? $file->share_views_count.' / '.$file->share_max_views
            : $file->share_views_count.' / без ліміту';
        $expiresLabel = $expiresAt
            ? $expiresAt->format('d.m.Y H:i')
            : 'без дати';

        return [
            'token' => $file->share_token,
            'url' => $isEnabled ? route('share.files.show', $file->share_token) : null,
            'raw_url' => $isEnabled ? route('share.files.raw', $file->share_token) : null,
            'is_enabled' => $isEnabled,
            'status_label' => $isEnabled ? 'Активний' : 'Вимкнено',
            'share_max_views' => $file->share_max_views,
            'share_views_count' => $file->share_views_count,
            'share_remaining_views' => $file->share_remaining_views,
            'share_expires_at' => $expiresAt?->format('Y-m-d H:i:s'),
            'share_expires_at_input' => $expiresAt?->format('Y-m-d\TH:i'),
            'usage_label' => 'Переглядів: '.$viewsLabel.' · Доступний до: '.$expiresLabel,
        ];
    }

    private function folderSharePayload(FileFolder $folder): array
    {
        $isEnabled = (bool) $folder->share_token;
        $expiresAt = $folder->share_expires_at;
        $viewsLabel = $folder->share_max_views
            ? $folder->share_views_count.' / '.$folder->share_max_views
            : $folder->share_views_count.' / без ліміту';
        $expiresLabel = $expiresAt
            ? $expiresAt->format('d.m.Y H:i')
            : 'без дати';

        return [
            'token' => $folder->share_token,
            'url' => $isEnabled ? route('share.folders.show', $folder->share_token) : null,
            'is_enabled' => $isEnabled,
            'status_label' => $isEnabled ? 'Активний' : 'Вимкнено',
            'share_max_views' => $folder->share_max_views,
            'share_views_count' => $folder->share_views_count,
            'share_remaining_views' => $folder->share_remaining_views,
            'share_expires_at' => $expiresAt?->format('Y-m-d H:i:s'),
            'share_expires_at_input' => $expiresAt?->format('Y-m-d\TH:i'),
            'usage_label' => 'Переглядів: '.$viewsLabel.' · Доступний до: '.$expiresLabel,
        ];
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
}
