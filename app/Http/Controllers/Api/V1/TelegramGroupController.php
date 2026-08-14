<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\TelegramStorageGroupResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TelegramGroupController extends Controller
{
    /**
     * List the caller's own Telegram storage groups — the valid values for
     * telegram_storage_group_id on POST /files. Without this, a non-admin
     * user with groups of their own has no way to discover their ids via
     * the API and gets a hard 422 from FileController::store() until they
     * pick one. Users with no groups of their own fall back to the shared
     * system group automatically, so nothing to list for them here.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $groups = $request->user()
            ->telegramStorageGroups()
            ->with('botToken')
            ->orderByDesc('is_default')
            ->orderBy('title')
            ->get();

        return TelegramStorageGroupResource::collection($groups);
    }
}
