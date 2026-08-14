<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaletteController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $user = $request->user();
        $q = trim((string) $request->query('q', ''));

        if ($q === '') {
            return response()->json([
                'files'   => [],
                'folders' => [],
                'tags'    => [],
            ]);
        }

        $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $q).'%';

        // Protected files, and files in a password-protected folder, must
        // stay out of this global search too — it's a folder-agnostic view
        // just like "All files", tag filters, and the main search box (see
        // FileController::index()); without this a locked folder's files
        // (or an individually-protected file) would surface here even
        // though they're excluded everywhere else.
        $protectedFolderIds = $user->folders()->whereNotNull('password_hash')->pluck('id')->all();

        $files = $user->files()
            ->where('is_protected', false)
            ->when(! empty($protectedFolderIds), fn ($query) => $query->where(
                fn ($q2) => $q2->whereNull('folder_id')->orWhereNotIn('folder_id', $protectedFolderIds)
            ))
            ->where(function ($q2) use ($like) {
                $q2->where('original_name', 'like', $like)
                    ->orWhere('mime_type', 'like', $like)
                    ->orWhere('extension', 'like', $like);
            })
            ->latest('updated_at')
            ->limit(8)
            ->get(['id', 'original_name', 'mime_type', 'extension', 'size', 'folder_id', 'is_protected'])
            ->map(fn ($f) => [
                'id'        => $f->id,
                'name'      => $f->original_name,
                'mime'      => $f->mime_type,
                'ext'       => $f->extension,
                'size'      => $f->human_size,
                'category'  => $f->type_category,
                'label'     => $f->type_label,
                'protected' => (bool) $f->is_protected,
                'preview'   => route('files.preview', $f),
                'gradient'  => $f->placeholder_gradient,
            ]);

        $folders = $user->folders()
            ->where('name', 'like', $like)
            ->withCount('files')
            ->orderByDesc('files_count')
            ->limit(8)
            ->get(['id', 'name', 'color'])
            ->map(fn ($folder) => [
                'id'    => $folder->id,
                'name'  => $folder->name,
                'count' => $folder->files_count,
                'color' => $folder->color,
                'href'  => route('files.index', ['folder' => $folder->id]),
            ]);

        // Count only files that would actually show up under this tag in the
        // "All files" context (see FileController::index()) — otherwise a tag
        // whose files are all protected shows a non-zero count here but
        // navigating to it lands on an empty list.
        $tags = $user->tags()
            ->where('name', 'like', $like)
            ->withCount(['files' => function ($q) use ($protectedFolderIds) {
                $q->where('is_protected', false);

                if (! empty($protectedFolderIds)) {
                    $q->where(function ($q2) use ($protectedFolderIds) {
                        $q2->whereNull('folder_id')->orWhereNotIn('folder_id', $protectedFolderIds);
                    });
                }
            }])
            ->orderByDesc('files_count')
            ->limit(8)
            ->get(['id', 'name'])
            ->map(fn ($tag) => [
                'id'    => $tag->id,
                'name'  => $tag->name,
                'count' => $tag->files_count,
                'href'  => route('files.index', ['tag' => $tag->name]),
            ]);

        return response()->json([
            'files'   => $files,
            'folders' => $folders,
            'tags'    => $tags,
        ]);
    }
}
