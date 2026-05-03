<?php

use App\Http\Controllers\TelegramWebhookController;
use App\Http\Controllers\TelegramStorageWebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/telegram/webhook/{secret}', TelegramWebhookController::class)
    ->name('telegram.webhook');

Route::post('/telegram/storage/{bot}/{secret}', TelegramStorageWebhookController::class)
    ->name('telegram.storage-webhook');
