<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManagedFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'folder_id',
        'storage_driver',
        'telegram_bot_token_id',
        'telegram_storage_group_id',
        'telegram_chat_id',
        'telegram_message_id',
        'telegram_file_id',
        'telegram_file_unique_id',
        'telegram_response',
        'original_name',
        'stored_name',
        'path',
        'mime_type',
        'extension',
        'size',
        'share_token',
        'share_max_views',
        'share_views_count',
        'share_expires_at',
    ];

    protected $casts = [
        'size' => 'integer',
        'share_max_views' => 'integer',
        'share_views_count' => 'integer',
        'share_expires_at' => 'datetime',
        'telegram_message_id' => 'integer',
        'telegram_response' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(FileFolder::class, 'folder_id');
    }

    public function telegramBotToken(): BelongsTo
    {
        return $this->belongsTo(TelegramBotToken::class);
    }

    public function telegramStorageGroup(): BelongsTo
    {
        return $this->belongsTo(TelegramStorageGroup::class);
    }

    public function getHumanSizeAttribute(): string
    {
        return self::formatBytes($this->size);
    }

    public function getTypeLabelAttribute(): string
    {
        if ($this->extension) {
            return strtoupper($this->extension);
        }

        return 'FILE';
    }

    public function getIsImageAttribute(): bool
    {
        return str_starts_with((string) $this->mime_type, 'image/');
    }

    public function getIsTextAttribute(): bool
    {
        if (str_starts_with((string) $this->mime_type, 'text/')) {
            return true;
        }

        return in_array($this->extension, [
            'css',
            'csv',
            'htm',
            'html',
            'js',
            'json',
            'log',
            'md',
            'php',
            'txt',
            'xml',
            'yaml',
            'yml',
        ], true);
    }

    public function getIsPreviewableAttribute(): bool
    {
        return $this->is_image || $this->is_text;
    }

    public function getIsTelegramAttribute(): bool
    {
        return $this->storage_driver === 'telegram';
    }

    public function getStorageLabelAttribute(): string
    {
        if ($this->is_telegram) {
            return $this->telegramStorageGroup?->title
                ? 'Telegram: '.$this->telegramStorageGroup->title
                : 'Telegram';
        }

        return 'Локальне сховище';
    }

    public function getShareRemainingViewsAttribute(): ?int
    {
        if ($this->share_max_views === null) {
            return null;
        }

        return max(0, $this->share_max_views - $this->share_views_count);
    }

    public function getShareIsExpiredAttribute(): bool
    {
        return $this->share_expires_at !== null && now()->greaterThan($this->share_expires_at);
    }

    public function getShareLimitReachedAttribute(): bool
    {
        return $this->share_max_views !== null && $this->share_views_count >= $this->share_max_views;
    }

    public static function formatBytes(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = min((int) floor(log($bytes, 1024)), count($units) - 1);
        $value = $bytes / (1024 ** $power);

        return number_format($value, $power === 0 ? 0 : 1).' '.$units[$power];
    }
}
