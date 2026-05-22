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
        ]);

        $folder = $user->folders()->create($validated);
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
        ]);

        $folder->update($validated);
        $folder->loadCount('files');

        return new FileFolderResource($folder);
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
