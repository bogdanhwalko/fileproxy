<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\FileFolderResource;
use App\Models\FileFolder;
use App\Models\User;
use App\Services\ManagedFileStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class FolderController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $folders = $request->user()
            ->folders()
            ->withCount('files')
            ->orderBy('name')
            ->get();

        return FileFolderResource::collection($folders);
    }

    public function show(Request $request, FileFolder $folder): FileFolderResource
    {
        $this->ensureOwner($request->user(), $folder);
        $folder->loadCount('files');

        return new FileFolderResource($folder);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('file_folders', 'name')->where('user_id', $user->id),
            ],
            'color' => ['nullable', 'string', Rule::in(array_keys(FileFolder::COLOR_PALETTE))],
            // Mirrors the web UI's rule: a password can only be set at creation
            // time, never added to an already-existing unprotected folder — see
            // setPassword() below for why.
            'password' => ['nullable', 'string', 'min:4', 'max:128'],
        ]);

        $folder = $user->folders()->create([
            'name' => $validated['name'],
            'color' => FileFolder::normalizeColor($validated['color'] ?? null),
        ]);

        if (! empty($validated['password'])) {
            $folder->setFolderPassword($validated['password']);
            $folder->save();
        }

        $folder->loadCount('files');

        return (new FileFolderResource($folder))
            ->response()
            ->setStatusCode(201);
    }

    public function update(Request $request, FileFolder $folder): FileFolderResource
    {
        $this->ensureOwner($request->user(), $folder);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('file_folders', 'name')
                    ->where('user_id', $request->user()->id)
                    ->ignore($folder->id),
            ],
            'color' => ['nullable', 'string', Rule::in(array_keys(FileFolder::COLOR_PALETTE))],
        ]);

        $folder->update([
            'name' => $validated['name'],
            'color' => FileFolder::normalizeColor($validated['color'] ?? null),
        ]);
        $folder->loadCount('files');

        return new FileFolderResource($folder);
    }

    /**
     * Change the password of an already-protected folder. Adding a password
     * to a folder that doesn't have one is intentionally not allowed here —
     * it can only be set at creation (see store()), so existing files never
     * silently become "encrypted" after the fact from a client's perspective.
     */
    public function setPassword(Request $request, FileFolder $folder): JsonResponse
    {
        $this->ensureOwner($request->user(), $folder);

        if (! $folder->is_password_protected) {
            return response()->json([
                'message' => 'A password can only be set when the folder is created.',
            ], 422);
        }

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:4', 'max:128'],
        ]);

        if (! $folder->verifyFolderPassword($validated['current_password'])) {
            return response()->json(['message' => 'Current password is incorrect.'], 422);
        }

        $folder->setFolderPassword($validated['password']);
        $folder->save();

        return response()->json(['message' => 'Password updated.']);
    }

    public function removePassword(Request $request, FileFolder $folder): JsonResponse
    {
        $this->ensureOwner($request->user(), $folder);

        if (! $folder->is_password_protected) {
            return response()->json(['message' => 'Folder is not password protected.'], 422);
        }

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
        ]);

        if (! $folder->verifyFolderPassword($validated['current_password'])) {
            return response()->json(['message' => 'Current password is incorrect.'], 422);
        }

        $folder->setFolderPassword(null);
        $folder->save();

        return response()->json(['message' => 'Password removed.']);
    }

    public function destroy(Request $request, FileFolder $folder, ManagedFileStorageService $fileStorage): JsonResponse
    {
        $this->ensureOwner($request->user(), $folder);

        $folder->files()
            ->with(['telegramBotToken', 'telegramStorageGroup.botToken'])
            ->chunkById(100, function ($files) use ($fileStorage): void {
                foreach ($files as $file) {
                    try {
                        $fileStorage->delete($file);
                    } catch (\Throwable $exception) {
                        report($exception);
                        $file->delete();
                    }
                }
            });

        Storage::disk('local')->deleteDirectory('uploads/'.$folder->user_id.'/folders/'.$folder->id);
        $folder->delete();

        return response()->json(['message' => 'Folder deleted.']);
    }

    private function ensureOwner(?User $user, FileFolder $folder): void
    {
        abort_unless($user && (int) $folder->user_id === (int) $user->id, 404);
    }
}
