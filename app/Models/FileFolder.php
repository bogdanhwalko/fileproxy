<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Hash;

class FileFolder extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'color',
        'password_hash',
        'password_set_at',
        'share_token',
        'share_max_views',
        'share_views_count',
        'share_expires_at',
    ];

    protected $hidden = [
        'password_hash',
    ];

    /** Palette of 8 supported folder colors (UI radio picker). */
    public const COLOR_PALETTE = [
        'slate'  => '#64748b',
        'red'    => '#dc2626',
        'orange' => '#ea580c',
        'amber'  => '#d97706',
        'green'  => '#16a34a',
        'teal'   => '#0d9488',
        'blue'   => '#2563eb',
        'indigo' => '#4f46e5',
        'purple' => '#9333ea',
        'pink'   => '#db2777',
    ];

    public static function normalizeColor(?string $color): ?string
    {
        if ($color === null || $color === '') {
            return null;
        }

        return array_key_exists($color, self::COLOR_PALETTE) ? $color : null;
    }

    protected $casts = [
        'share_max_views' => 'integer',
        'share_views_count' => 'integer',
        'share_expires_at' => 'datetime',
        'password_set_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(ManagedFile::class, 'folder_id');
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

    /** True when this folder requires a password to view its files. */
    public function getIsPasswordProtectedAttribute(): bool
    {
        return ! empty($this->password_hash);
    }

    /**
     * Hash and set a password. Pass null/empty to clear.
     */
    public function setFolderPassword(?string $plain): void
    {
        if ($plain === null || $plain === '') {
            $this->password_hash = null;
            $this->password_set_at = null;
        } else {
            $this->password_hash = Hash::make($plain);
            $this->password_set_at = now();
        }
    }

    public function verifyFolderPassword(string $plain): bool
    {
        if (empty($this->password_hash)) {
            return false;
        }

        return Hash::check($plain, $this->password_hash);
    }
}
