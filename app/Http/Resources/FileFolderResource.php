<?php

namespace App\Http\Resources;

use App\Models\FileFolder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin FileFolder
 */
class FileFolderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $share = null;

        if ($this->share_token) {
            $share = [
                'token' => $this->share_token,
                'url' => route('share.folders.show', $this->share_token),
                'max_views' => $this->share_max_views,
                'views_count' => $this->share_views_count,
                'remaining_views' => $this->share_remaining_views,
                'expires_at' => optional($this->share_expires_at)->toIso8601String(),
                'is_expired' => $this->share_is_expired,
                'limit_reached' => $this->share_limit_reached,
            ];
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'color' => $this->color,
            'files_count' => $this->when(isset($this->files_count), fn () => (int) $this->files_count),
            'is_password_protected' => $this->is_password_protected,
            'password_set_at' => optional($this->password_set_at)->toIso8601String(),
            'share' => $share,
            'created_at' => optional($this->created_at)->toIso8601String(),
            'updated_at' => optional($this->updated_at)->toIso8601String(),
            'links' => [
                'self' => route('api.v1.folders.show', $this->id),
            ],
        ];
    }
}
