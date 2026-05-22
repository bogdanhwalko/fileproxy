<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\FileFolderResource;
use App\Http\Resources\ManagedFileResource;
use App\Models\FileFolder;
use App\Models\ManagedFile;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ShareController extends Controller
{
    public function enableFile(Request $request, ManagedFile $file): ManagedFileResource
    {
        $this->ensureFileOwner($request->user(), $file);

        if (! $file->share_token) {
            $file->forceFill([
                'share_token' => $this->uniqueToken(ManagedFile::class),
                'share_views_count' => 0,
            ])->save();
        }

        return new ManagedFileResource($file->refresh());
    }

    public function updateFile(Request $request, ManagedFile $file): ManagedFileResource|JsonResponse
    {
        $this->ensureFileOwner($request->user(), $file);

        if (! $file->share_token) {
            return response()->json(['message' => 'Sharing is disabled for this file.'], 422);
        }

        $validated = $request->validate([
            'share_max_views' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'share_expires_at' => ['nullable', 'date', 'after:now'],
        ]);

        $file->forceFill([
            'share_max_views' => $validated['share_max_views'] ?? null,
            'share_expires_at' => ! empty($validated['share_expires_at'])
                ? Carbon::parse($validated['share_expires_at'])
                : null,
        ])->save();

        return new ManagedFileResource($file->refresh());
    }

    public function disableFile(Request $request, ManagedFile $file): JsonResponse
    {
        $this->ensureFileOwner($request->user(), $file);

        $file->forceFill([
            'share_token' => null,
            'share_max_views' => null,
            'share_views_count' => 0,
            'share_expires_at' => null,
        ])->save();

        return response()->json(['message' => 'Public access to the file disabled.']);
    }

    public function enableFolder(Request $request, FileFolder $folder): FileFolderResource
    {
        $this->ensureFolderOwner($request->user(), $folder);

        if (! $folder->share_token) {
            $folder->forceFill([
                'share_token' => $this->uniqueToken(FileFolder::class),
                'share_views_count' => 0,
            ])->save();
        }

        return new FileFolderResource($folder->refresh());
    }

    public function updateFolder(Request $request, FileFolder $folder): FileFolderResource|JsonResponse
    {
        $this->ensureFolderOwner($request->user(), $folder);

        if (! $folder->share_token) {
            return response()->json(['message' => 'Sharing is disabled for this folder.'], 422);
        }

        $validated = $request->validate([
            'share_max_views' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'share_expires_at' => ['nullable', 'date', 'after:now'],
        ]);

        $folder->forceFill([
            'share_max_views' => $validated['share_max_views'] ?? null,
            'share_expires_at' => ! empty($validated['share_expires_at'])
                ? Carbon::parse($validated['share_expires_at'])
                : null,
        ])->save();

        return new FileFolderResource($folder->refresh());
    }

    public function disableFolder(Request $request, FileFolder $folder): JsonResponse
    {
        $this->ensureFolderOwner($request->user(), $folder);

        $folder->forceFill([
            'share_token' => null,
            'share_max_views' => null,
            'share_views_count' => 0,
            'share_expires_at' => null,
        ])->save();

        return response()->json(['message' => 'Public access to the folder disabled.']);
    }

    private function ensureFileOwner(?User $user, ManagedFile $file): void
    {
        abort_unless($user && (int) $file->user_id === (int) $user->id, 404);
    }

    private function ensureFolderOwner(?User $user, FileFolder $folder): void
    {
        abort_unless($user && (int) $folder->user_id === (int) $user->id, 404);
    }

    private function uniqueToken(string $modelClass): string
    {
        do {
            $token = Str::random(48);
        } while ($modelClass::query()->where('share_token', $token)->exists());

        return $token;
    }
}
