<?php

namespace App\Http\Resources;

use App\Models\TelegramStorageGroup;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin TelegramStorageGroup
 */
class TelegramStorageGroupResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'is_default' => $this->is_default,
            'bot' => $this->whenLoaded('botToken', fn () => $this->botToken ? [
                'id' => $this->botToken->id,
                'name' => $this->botToken->name,
                'username' => $this->botToken->username,
            ] : null),
        ];
    }
}
