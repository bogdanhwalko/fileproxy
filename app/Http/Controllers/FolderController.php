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
        ], [
            'name.required' => 'Вкажіть назву папки.',
            'name.unique' => 'Папка з такою назвою вже існує.',
        ]);

        $folder = $user->folders()->create($validated);

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
        ], [
            'name.required' => 'Вкажіть назву папки.',
            'name.unique' => 'Папка з такою назвою вже існує.',
        ]);

        $folder->update($validated);

        return redirect()
            ->route('files.index', ['folder' => $folder->id])
            ->with('status', 'Папку перейменовано.');
    }

    public function destroy(FileFolder $folder, ManagedFileStorageService $fileStorage): RedirectResponse
    {
        abort_unless((int) $folder->user_id === (int) auth()->id(), 404);

        $files = $folder->files()
            ->with(['telegramBotToken', 'telegramStorageGroup.botToken'])
            ->get();

        foreach ($files as $file) {
            if ($file->is_telegram) {
                $file->delete();

                continue;
            }

            $fileStorage->delete($file);
        }

        Storage::disk('local')->deleteDirectory('uploads/'.$folder->user_id.'/folders/'.$folder->id);

        $folder->delete();

        return redirect()
            ->route('files.index')
            ->with('status', 'Папку видалено. Записи файлів із цієї папки також видалено.');
    }
}
