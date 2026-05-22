<?php

namespace App\Http\Resources;

use App\Models\ManagedFile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ManagedFile
 */
class ManagedFileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $share = null;

        if ($this->share_token) {
            $share = [
                'token' => $this->share_token,
                'url' => route('share.files.show', $this->share_token),
                'raw_url' => route('share.files.raw', $this->share_token),
                'download_url' => route('share.files.download', $this->share_token),
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
            'original_name' => $this->original_name,
            'mime_type' => $this->mime_type,
            'extension' => $this->extension,
            'size' => (int) $this->size,
            'human_size' => $this->human_size,
            'storage_driver' => $this->storage_driver,
            'storage_label' => $this->storage_label,
            'folder_id' => $this->folder_id,
            'folder_name' => $this->whenLoaded('folder', fn () => $this->folder?->name),
            'is_image' => $this->is_image,
            'is_text' => $this->is_text,
            'is_previewable' => $this->is_previewable,
            'is_telegram' => $this->is_telegram,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'upload_failure_reason' => $this->upload_failure_reason,
            'telegram' => $this->when($this->is_telegram, fn () => [
                'storage_group_id' => $this->telegram_storage_group_id,
                'storage_group_title' => $this->whenLoaded('telegramStorageGroup', fn () => $this->telegramStorageGroup?->title),
                'chat_id' => $this->telegram_chat_id,
                'message_id' => $this->telegram_message_id,
            ]),
            'share' => $share,
            'created_at' => optional($this->created_at)->toIso8601String(),
            'updated_at' => optional($this->updated_at)->toIso8601String(),
            'links' => [
                'self' => route('api.v1.files.show', $this->id),
                'content' => route('api.v1.files.content', $this->id),
            ],
        ];
    }
}
