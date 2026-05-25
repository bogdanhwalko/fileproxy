<?php

namespace App\Http\Controllers;

use App\Models\FileFolder;
use App\Services\FolderUnlockService;
use App\Services\ManagedFileStorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

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

    /**
     * Set or replace the folder password. If the folder already has one,
     * the current password is required.
     */
    public function setPassword(Request $request, FileFolder $folder, FolderUnlockService $unlock): RedirectResponse
    {
        abort_unless((int) $folder->user_id === (int) $request->user()->id, 404);

        $rules = [
            'password' => ['required', 'string', 'min:4', 'max:128'],
        ];
        if ($folder->is_password_protected) {
            $rules['current_password'] = ['required', 'string'];
        }

        $validated = $request->validate($rules, [
            'password.required' => 'Вкажіть новий пароль.',
            'password.min' => 'Пароль має бути не менш ніж 4 символи.',
            'current_password.required' => 'Введіть поточний пароль.',
        ]);

        if ($folder->is_password_protected && ! $folder->verifyFolderPassword($validated['current_password'])) {
            return redirect()
                ->route('files.index', ['folder' => $folder->id])
                ->withErrors(['current_password' => 'Поточний пароль невірний.']);
        }

        $folder->setFolderPassword($validated['password']);
        $folder->save();

        // Auto-unlock for the current session so the user can immediately work
        $unlock->unlock($folder);

        return redirect()
            ->route('files.index', ['folder' => $folder->id])
            ->with('status', 'Пароль збережено. Папка захищена.');
    }

    /**
     * Remove the password from the folder. Requires current password.
     */
    public function removePassword(Request $request, FileFolder $folder, FolderUnlockService $unlock): RedirectResponse
    {
        abort_unless((int) $folder->user_id === (int) $request->user()->id, 404);

        if (! $folder->is_password_protected) {
            return redirect()->route('files.index', ['folder' => $folder->id]);
        }

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
        ], [
            'current_password.required' => 'Введіть поточний пароль.',
        ]);

        if (! $folder->verifyFolderPassword($validated['current_password'])) {
            return redirect()
                ->route('files.index', ['folder' => $folder->id])
                ->withErrors(['current_password' => 'Поточний пароль невірний.']);
        }

        $folder->setFolderPassword(null);
        $folder->save();
        $unlock->lock($folder);

        return redirect()
            ->route('files.index', ['folder' => $folder->id])
            ->with('status', 'Пароль знято з папки.');
    }

    /**
     * Show the unlock prompt page for a password-protected folder.
     */
    public function unlockPrompt(Request $request, FileFolder $folder, FolderUnlockService $unlock): RedirectResponse|View
    {
        abort_unless((int) $folder->user_id === (int) $request->user()->id, 404);

        if (! $folder->is_password_protected) {
            return redirect()->route('files.index', ['folder' => $folder->id]);
        }

        if ($unlock->isUnlocked($folder)) {
            return redirect()->route('files.index', ['folder' => $folder->id]);
        }

        return view('folders.unlock', [
            'folder' => $folder,
        ]);
    }

    /**
     * Submit the password to unlock the folder for the current session.
     */
    public function unlock(Request $request, FileFolder $folder, FolderUnlockService $unlock): RedirectResponse
    {
        abort_unless((int) $folder->user_id === (int) $request->user()->id, 404);
        abort_unless($folder->is_password_protected, 404);

        $validated = $request->validate([
            'password' => ['required', 'string'],
        ], [
            'password.required' => 'Введіть пароль.',
        ]);

        if (! $folder->verifyFolderPassword($validated['password'])) {
            return back()->withErrors(['password' => 'Невірний пароль.']);
        }

        $unlock->unlock($folder);

        return redirect()->route('files.index', ['folder' => $folder->id]);
    }

    /**
     * Manually re-lock a folder (forget the session unlock).
     */
    public function lock(Request $request, FileFolder $folder, FolderUnlockService $unlock): RedirectResponse
    {
        abort_unless((int) $folder->user_id === (int) $request->user()->id, 404);

        $unlock->lock($folder);

        return redirect()->route('files.index');
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
