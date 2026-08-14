<?php

namespace App\Http\Controllers\Concerns;

/**
 * Storage-by-category breakdown, shared by the web StatsController and the
 * api.v1 one so the category palette/labels can't drift between the two.
 */
trait ComputesFileStats
{
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
                    'key' => $key,
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
