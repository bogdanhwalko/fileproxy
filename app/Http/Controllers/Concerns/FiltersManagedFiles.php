<?php

namespace App\Http\Controllers\Concerns;

use Carbon\Carbon;
use Throwable;

/**
 * Shared query filters + archive-naming helpers for listing/searching/zipping
 * a user's files. Used by both the web FileController and the api.v1 one so
 * search/type/date-range semantics can't drift between the two surfaces.
 */
trait FiltersManagedFiles
{
    private function applySearchFilter($query, string $search)
    {
        $search = trim($search);

        if ($search === '') {
            return $query;
        }

        $driver = $query->getModel()->getConnection()->getDriverName();
        $useFulltext = in_array($driver, ['mysql', 'mariadb'], true)
            && mb_strlen($search) >= 3
            && ! preg_match('/[%_]/', $search);

        if ($useFulltext) {
            $boolean = $this->buildFulltextBooleanQuery($search);
            $likeFallback = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $search).'%';

            return $query->where(function ($query) use ($boolean, $likeFallback, $search) {
                $query->whereRaw('MATCH(original_name) AGAINST (? IN BOOLEAN MODE)', [$boolean])
                    ->orWhere('original_name', 'like', $likeFallback)
                    ->orWhere('mime_type', 'like', '%'.$search.'%')
                    ->orWhere('extension', '=', strtolower($search));
            });
        }

        $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $search).'%';

        return $query->where(function ($query) use ($like, $search) {
            $query
                ->where('original_name', 'like', $like)
                ->orWhere('mime_type', 'like', $like)
                ->orWhere('extension', 'like', '%'.strtolower($search).'%');
        });
    }

    private function buildFulltextBooleanQuery(string $search): string
    {
        $tokens = preg_split('/\s+/u', $search) ?: [];
        $parts = [];

        foreach ($tokens as $token) {
            $token = preg_replace('/[+\-><()~*"@]+/u', '', $token);

            if ($token === null || mb_strlen($token) < 2) {
                continue;
            }

            $parts[] = '+'.$token.'*';
        }

        return $parts === [] ? $search : implode(' ', $parts);
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

    /**
     * @return array{0: ?Carbon, 1: ?Carbon}
     */
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
