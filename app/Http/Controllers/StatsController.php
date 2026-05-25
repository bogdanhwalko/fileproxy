<?php

namespace App\Http\Controllers;

use App\Models\ManagedFile;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StatsController extends Controller
{
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

    /**
     * @return array<int, array{key:string, label:string, color:string, bytes:int}>
     */
    private function storageByCategory($user): array
    {
        $palette = [
            'image'        => ['Зображення',  '#16a34a'],
            'video'        => ['Відео',       '#2563eb'],
            'audio'        => ['Аудіо',       '#db2777'],
            'document'     => ['Документи',   '#d97706'],
            'spreadsheet'  => ['Таблиці',     '#047857'],
            'presentation' => ['Презентації', '#ea580c'],
            'archive'      => ['Архіви',      '#7c3aed'],
            'code'         => ['Код',         '#0284c7'],
            'design'       => ['Дизайн',      '#a21caf'],
            'font'         => ['Шрифти',      '#3730a3'],
            'ebook'        => ['Книги',       '#b91c1c'],
            'other'        => ['Інше',        '#64748b'],
        ];

        $totals = array_fill_keys(array_keys($palette), 0);

        $user->files()
            ->select(['mime_type', 'extension', 'size'])
            ->chunk(500, function ($files) use (&$totals): void {
                foreach ($files as $file) {
                    $cat = $file->type_category;
                    $totals[$cat] = ($totals[$cat] ?? 0) + (int) $file->size;
                }
            });

        $result = [];
        foreach ($palette as $key => [$label, $color]) {
            if (($totals[$key] ?? 0) > 0) {
                $result[] = [
                    'key'   => $key,
                    'label' => $label,
                    'color' => $color,
                    'bytes' => (int) $totals[$key],
                ];
            }
        }

        usort($result, fn ($a, $b) => $b['bytes'] <=> $a['bytes']);

        return $result;
    }
}
