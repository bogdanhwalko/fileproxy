<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ComputesFileStats;
use App\Models\ManagedFile;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StatsController extends Controller
{
    use ComputesFileStats;

    public function index(Request $request): View
    {
        $user = $request->user();

        $stats = [
            'total' => $user->files()->count(),
            'storage' => ManagedFile::formatBytes((int) $user->files()->sum('size')),
            'folders' => $user->folders()->count(),
            'telegram' => $user->files()->where('storage_driver', 'telegram')->count(),
            'local' => $user->files()->where('storage_driver', 'local')->count(),
            'root' => $user->files()->whereNull('folder_id')->count(),
            'shared_files' => $user->files()->whereNotNull('share_token')->count(),
        ];

        $largestFiles = $user->files()
            ->orderByDesc('size')
            ->limit(5)
            ->get();

        $recentFiles = $user->files()
            ->latest()
            ->limit(5)
            ->get();

        $foldersByFiles = $user->folders()
            ->withCount('files')
            ->orderByDesc('files_count')
            ->limit(8)
            ->get();

        // Storage usage breakdown by file category (computed in PHP since type_category
        // is derived from mime+extension, not a DB column).
        $storageByCategory = $this->storageByCategory($user);

        return view('stats.index', [
            'stats' => $stats,
            'largestFiles' => $largestFiles,
            'recentFiles' => $recentFiles,
            'foldersByFiles' => $foldersByFiles,
            'storageByCategory' => $storageByCategory,
        ]);
    }

}
