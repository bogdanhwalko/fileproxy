<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ManagedFile extends Model
{
    use HasFactory;

    public const STATUS_UPLOADED = 'uploaded';

    public const STATUS_PENDING = 'pending';

    public const STATUS_FAILED = 'failed';

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
        'original_name',
        'stored_name',
        'path',
        'mime_type',
        'extension',
        'size',
        'status',
        'upload_failure_reason',
        'share_token',
        'share_max_views',
        'share_views_count',
        'share_expires_at',
        'is_protected',
        'encryption_key',
        'encryption_method',
        'chunk_count',
        'original_size',
    ];

    protected $casts = [
        'size' => 'integer',
        'share_max_views' => 'integer',
        'share_views_count' => 'integer',
        'share_expires_at' => 'datetime',
        'telegram_message_id' => 'integer',
        'is_protected' => 'boolean',
        'encryption_key' => 'encrypted',
        'chunk_count' => 'integer',
        'original_size' => 'integer',
    ];

    protected $attributes = [
        'status' => self::STATUS_UPLOADED,
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

    public function chunks(): HasMany
    {
        return $this->hasMany(ManagedFileChunk::class)->orderBy('sequence');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'managed_file_tag')->orderBy('tags.name')->withTimestamps();
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

    /**
     * Coarse-grained type category used for color-coding badges in the UI.
     * Returns one of: image, video, audio, document, spreadsheet, presentation,
     * archive, code, design, font, ebook, other.
     */
    public function getTypeCategoryAttribute(): string
    {
        $mime = strtolower((string) $this->mime_type);
        $ext  = strtolower((string) $this->extension);

        if (str_starts_with($mime, 'image/') || in_array($ext, ['jpg','jpeg','png','gif','webp','svg','svgz','bmp','tiff','ico','heic','avif'], true)) {
            return 'image';
        }
        if (str_starts_with($mime, 'video/') || in_array($ext, ['mp4','mov','avi','mkv','webm','flv','wmv','m4v','mpeg','3gp'], true)) {
            return 'video';
        }
        if (str_starts_with($mime, 'audio/') || in_array($ext, ['mp3','wav','ogg','flac','aac','m4a','wma','opus'], true)) {
            return 'audio';
        }
        if (in_array($ext, ['xls','xlsx','csv','ods','numbers'], true)) {
            return 'spreadsheet';
        }
        if (in_array($ext, ['ppt','pptx','odp','key'], true)) {
            return 'presentation';
        }
        if (in_array($ext, ['pdf','doc','docx','odt','rtf','txt','md','tex','pages'], true) || str_starts_with($mime, 'text/')) {
            return 'document';
        }
        if (in_array($ext, ['zip','rar','7z','tar','gz','bz2','xz','tgz','iso'], true)) {
            return 'archive';
        }
        if (in_array($ext, ['php','js','ts','jsx','tsx','py','rb','go','rs','java','c','cpp','cs','swift','kt','sh','sql','json','xml','yml','yaml','html','htm','css','scss','vue'], true)) {
            return 'code';
        }
        if (in_array($ext, ['psd','ai','sketch','fig','xd','blend','obj','stl'], true)) {
            return 'design';
        }
        if (in_array($ext, ['ttf','otf','woff','woff2','eot'], true)) {
            return 'font';
        }
        if (in_array($ext, ['epub','mobi','fb2','azw','azw3'], true)) {
            return 'ebook';
        }

        return 'other';
    }

    /**
     * Deterministic 2-stop HSL gradient used as the blur-up placeholder
     * behind every image preview. Same file → same gradient across renders.
     */
    public function getPlaceholderGradientAttribute(): string
    {
        $seed = (string) ($this->id ?? $this->original_name ?? 'x');
        $hash = abs(crc32($seed));
        $hue  = $hash % 360;
        $hue2 = ($hue + 35) % 360;

        return "linear-gradient(135deg, hsl({$hue}, 36%, 78%), hsl({$hue2}, 42%, 62%))";
    }

    public function getIsImageAttribute(): bool
    {
        $mime = strtolower((string) $this->mime_type);
        $extension = strtolower((string) $this->extension);

        if ($mime === 'image/svg+xml' || $extension === 'svg' || $extension === 'svgz') {
            return false;
        }

        return str_starts_with($mime, 'image/');
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

    public function getIsPdfAttribute(): bool
    {
        $mime = strtolower((string) $this->mime_type);
        $ext = strtolower((string) $this->extension);

        return $mime === 'application/pdf' || $ext === 'pdf';
    }

    public function getIsPreviewableAttribute(): bool
    {
        return $this->is_image || $this->is_text || $this->is_pdf;
    }

    public function getIsTelegramAttribute(): bool
    {
        return $this->storage_driver === 'telegram';
    }

    public function getIsPendingAttribute(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function getIsFailedAttribute(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function getIsUploadedAttribute(): bool
    {
        return $this->status === self::STATUS_UPLOADED;
    }

    public function getStatusLabelAttribute(): ?string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Очікує завантаження в Telegram',
            self::STATUS_FAILED => 'Помилка завантаження',
            default => null,
        };
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
