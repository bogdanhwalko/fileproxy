<?php

namespace App\Http\Controllers;

use App\Models\FileFolder;
use App\Services\ManagedFileStorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class FolderController extends Controller
{
    public function store(Request $request): RedirectResponse
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
        ], [
            'name.required' => 'Вкажіть назву папки.',
            'name.unique' => 'Папка з такою назвою вже існує.',
        ]);

        $folder = $user->folders()->create([
            'name'  => $validated['name'],
            'color' => FileFolder::normalizeColor($validated['color'] ?? null),
        ]);

        return redirect()
            ->route('files.index', ['folder' => $folder->id])
            ->with('status', 'Папку створено.');
    }

    public function update(Request $request, FileFolder $folder): RedirectResponse
    {
        abort_unless((int) $folder->user_id === (int) $request->user()->id, 404);

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
        ], [
            'name.required' => 'Вкажіть назву папки.',
            'name.unique' => 'Папка з такою назвою вже існує.',
        ]);

        $folder->update([
            'name'  => $validated['name'],
            'color' => FileFolder::normalizeColor($validated['color'] ?? null),
        ]);

        return redirect()
            ->route('files.index', ['folder' => $folder->id])
            ->with('status', 'Папку оновлено.');
    }

    public function destroy(FileFolder $folder, ManagedFileStorageService $fileStorage): RedirectResponse
    {
        abort_unless((int) $folder->user_id === (int) auth()->id(), 404);

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

        return redirect()
            ->route('files.index')
            ->with('status', 'Папку видалено. Записи файлів із цієї папки також видалено.');
    }
}
