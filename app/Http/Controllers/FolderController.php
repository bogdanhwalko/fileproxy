<?php

namespace App\Http\Controllers;

use App\Models\FileFolder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function destroy(FileFolder $folder): RedirectResponse
    {
        abort_unless((int) $folder->user_id === (int) auth()->id(), 404);

        $folder->delete();

        return redirect()
            ->route('files.index')
            ->with('status', 'Папку видалено. Файли з цієї папки перенесено до розділу без папки.');
    }
}
