<?php

use App\Http\Controllers\Api\V1\FileController as ApiFileController;
use App\Http\Controllers\Api\V1\FolderController as ApiFolderController;
use App\Http\Controllers\Api\V1\ShareController as ApiShareController;
use App\Http\Controllers\TelegramStorageWebhookController;
use App\Http\Controllers\TelegramWebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/telegram/webhook/{secret}', TelegramWebhookController::class)
    ->name('telegram.webhook');

Route::post('/telegram/storage/{bot}/{secret}', TelegramStorageWebhookController::class)
    ->name('telegram.storage-webhook');

Route::prefix('v1')
    ->name('api.v1.')
    ->middleware(['auth:sanctum', 'api.not_blocked'])
    ->group(function () {

        Route::get('/user', fn (Request $request) => $request->user()->only(['id', 'name', 'phone', 'is_admin']))
            ->name('user');

        Route::get('/files', [ApiFileController::class, 'index'])->name('files.index');
        Route::post('/files', [ApiFileController::class, 'store'])
            ->middleware('throttle:api-uploads')
            ->name('files.store');
        Route::get('/files/{file}', [ApiFileController::class, 'show'])->name('files.show');
        Route::get('/files/{file}/content', [ApiFileController::class, 'content'])->name('files.content');
        Route::delete('/files/{file}', [ApiFileController::class, 'destroy'])->name('files.destroy');

        Route::post('/files/{file}/share', [ApiShareController::class, 'enableFile'])->name('files.share.enable');
        Route::patch('/files/{file}/share', [ApiShareController::class, 'updateFile'])->name('files.share.update');
        Route::delete('/files/{file}/share', [ApiShareController::class, 'disableFile'])->name('files.share.disable');

        Route::get('/folders', [ApiFolderController::class, 'index'])->name('folders.index');
        Route::post('/folders', [ApiFolderController::class, 'store'])->name('folders.store');
        Route::get('/folders/{folder}', [ApiFolderController::class, 'show'])->name('folders.show');
        Route::patch('/folders/{folder}', [ApiFolderController::class, 'update'])->name('folders.update');
        Route::delete('/folders/{folder}', [ApiFolderController::class, 'destroy'])->name('folders.destroy');

        Route::post('/folders/{folder}/share', [ApiShareController::class, 'enableFolder'])->name('folders.share.enable');
        Route::patch('/folders/{folder}/share', [ApiShareController::class, 'updateFolder'])->name('folders.share.update');
        Route::delete('/folders/{folder}/share', [ApiShareController::class, 'disableFolder'])->name('folders.share.disable');
    });
