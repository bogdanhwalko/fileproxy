<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\ComputesFileStats;
use App\Http\Controllers\Controller;
use App\Http\Resources\ManagedFileResource;
use App\Models\ManagedFile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StatsController extends Controller
{
    use ComputesFileStats;

    /**
     * JSON equivalent of the web /stats dashboard.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $totalBytes = (int) $user->files()->sum('size');

        $largestFiles = $user->files()->with(['folder'])->orderByDesc('size')->limit(5)->get();
        $recentFiles = $user->files()->with(['folder'])->latest()->limit(5)->get();

        $foldersByFiles = $user->folders()
            ->withCount('files')
            ->orderByDesc('files_count')
            ->limit(8)
            ->get()
            ->map(fn ($folder) => [
                'id' => $folder->id,
                'name' => $folder->name,
                'files_count' => (int) $folder->files_count,
            ])
            ->values();

        return response()->json([
            'total' => $user->files()->count(),
            'storage_bytes' => $totalBytes,
            'storage_human' => ManagedFile::formatBytes($totalBytes),
            'folders' => $user->folders()->count(),
            'telegram' => $user->files()->where('storage_driver', 'telegram')->count(),
            'local' => $user->files()->where('storage_driver', 'local')->count(),
            'root' => $user->files()->whereNull('folder_id')->count(),
            'shared_files' => $user->files()->whereNotNull('share_token')->count(),
            'largest_files' => ManagedFileResource::collection($largestFiles),
            'recent_files' => ManagedFileResource::collection($recentFiles),
            'folders_by_files' => $foldersByFiles,
            'storage_by_category' => $this->storageByCategory($user),
        ]);
    }
}
