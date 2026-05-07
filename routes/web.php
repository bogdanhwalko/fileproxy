<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\FolderController;
use App\Http\Controllers\ShareController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\TelegramSetupController;
use App\Http\Controllers\TelegramStorageSettingsController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');

Route::middleware('throttle:share-download')->group(function () {
    Route::get('/share/files/{token}', [ShareController::class, 'showFile'])->name('share.files.show');
    Route::get('/share/files/{token}/inline', [ShareController::class, 'inlineFile'])->name('share.files.inline');
    Route::get('/share/files/{token}/download', [ShareController::class, 'downloadFile'])->name('share.files.download');
    Route::get('/share/folders/{token}', [ShareController::class, 'showFolder'])->name('share.folders.show');
    Route::get('/share/folders/{token}/download', [ShareController::class, 'downloadFolder'])->name('share.folders.download');
    Route::get('/share/folders/{token}/files/{file}', [ShareController::class, 'showFolderFile'])->name('share.folders.files.show');
    Route::get('/share/folders/{token}/files/{file}/inline', [ShareController::class, 'inlineFolderFile'])->name('share.folders.files.inline');
    Route::get('/share/folders/{token}/files/{file}/download', [ShareController::class, 'downloadFolderFile'])->name('share.folders.files.download');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:auth-phone')
        ->name('login.store');

    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])
        ->middleware('throttle:auth-phone')
        ->name('register.store');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth', 'not.blocked'])->group(function () {
    Route::get('/stats', [StatsController::class, 'index'])->name('stats.index');
    Route::get('/files', [FileController::class, 'index'])->name('files.index');
    Route::get('/files/archive', [FileController::class, 'downloadArchive'])->name('files.archive');
    Route::post('/files', [FileController::class, 'store'])
        ->middleware('throttle:uploads')
        ->name('files.store');
    Route::get('/files/{file}/preview', [FileController::class, 'preview'])->name('files.preview');
    Route::get('/files/{file}/inline', [FileController::class, 'inline'])->name('files.inline');
    Route::get('/files/{file}/download', [FileController::class, 'download'])->name('files.download');
    Route::post('/files/{file}/share', [ShareController::class, 'shareFile'])->name('files.share');
    Route::patch('/files/{file}/share', [ShareController::class, 'updateFileShareSettings'])->name('files.share.update');
    Route::delete('/files/{file}/share', [ShareController::class, 'unshareFile'])->name('files.share.destroy');
    Route::delete('/files/{file}', [FileController::class, 'destroy'])->name('files.destroy');

    Route::post('/folders', [FolderController::class, 'store'])->name('folders.store');
    Route::patch('/folders/{folder}', [FolderController::class, 'update'])->name('folders.update');
    Route::post('/folders/{folder}/share', [ShareController::class, 'shareFolder'])->name('folders.share');
    Route::patch('/folders/{folder}/share', [ShareController::class, 'updateFolderShareSettings'])->name('folders.share.update');
    Route::delete('/folders/{folder}/share', [ShareController::class, 'unshareFolder'])->name('folders.share.destroy');
    Route::delete('/folders/{folder}', [FolderController::class, 'destroy'])->name('folders.destroy');

    Route::get('/telegram/setup', TelegramSetupController::class)->name('telegram.setup');
    Route::get('/settings/telegram', [TelegramStorageSettingsController::class, 'index'])->name('telegram-settings.index');
    Route::post('/settings/telegram/bots', [TelegramStorageSettingsController::class, 'storeBot'])->name('telegram-settings.bots.store');
    Route::post('/settings/telegram/bots/{bot}/default', [TelegramStorageSettingsController::class, 'makeDefaultBot'])->name('telegram-settings.bots.default');
    Route::post('/settings/telegram/bots/{bot}/repair', [TelegramStorageSettingsController::class, 'repairBot'])->name('telegram-settings.bots.repair');
    Route::post('/settings/telegram/bots/{bot}/sync', [TelegramStorageSettingsController::class, 'syncBot'])->name('telegram-settings.bots.sync');
    Route::delete('/settings/telegram/bots/{bot}', [TelegramStorageSettingsController::class, 'destroyBot'])->name('telegram-settings.bots.destroy');
    Route::post('/settings/telegram/groups', [TelegramStorageSettingsController::class, 'storeGroup'])->name('telegram-settings.groups.store');
    Route::post('/settings/telegram/groups/{group}/default', [TelegramStorageSettingsController::class, 'makeDefaultGroup'])->name('telegram-settings.groups.default');
    Route::post('/settings/telegram/groups/{group}/global-default', [TelegramStorageSettingsController::class, 'makeGlobalDefaultGroup'])->name('telegram-settings.groups.global-default');
    Route::delete('/settings/telegram/groups/{group}/global-default', [TelegramStorageSettingsController::class, 'removeGlobalDefaultGroup'])->name('telegram-settings.groups.global-default.remove');
    Route::delete('/settings/telegram/groups/{group}', [TelegramStorageSettingsController::class, 'destroyGroup'])->name('telegram-settings.groups.destroy');

    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::redirect('/', '/admin/users')->name('index');
        Route::get('/users', [AdminController::class, 'users'])->name('users.index');
        Route::get('/users/{user}', [AdminController::class, 'showUser'])->name('users.show');
        Route::post('/users/{user}/block', [AdminController::class, 'blockUser'])->name('users.block');
        Route::post('/users/{user}/unblock', [AdminController::class, 'unblockUser'])->name('users.unblock');
        Route::get('/users/{user}/files/{file}/preview', [AdminController::class, 'previewFile'])->name('users.files.preview');
        Route::get('/users/{user}/files/{file}/inline', [AdminController::class, 'inlineFile'])->name('users.files.inline');
        Route::get('/users/{user}/files/{file}/download', [AdminController::class, 'downloadFile'])->name('users.files.download');
        Route::delete('/users/{user}/files/{file}', [AdminController::class, 'destroyFile'])->name('users.files.destroy');
    });
});
