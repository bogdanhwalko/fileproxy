<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class TelegramSetupController extends Controller
{
    public function __invoke(Request $request): View
    {
        return view('settings.telegram-setup', [
            'botTokens' => $request->user()->telegramBotTokens()
                ->orderByDesc('is_default')
                ->orderBy('name')
                ->get(),
            'storageGroups' => $request->user()->telegramStorageGroups()
                ->with('botToken')
                ->orderByDesc('is_default')
                ->orderBy('title')
                ->get(),
        ]);
    }
}
