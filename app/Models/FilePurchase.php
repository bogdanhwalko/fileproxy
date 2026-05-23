<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FilePurchase extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_PAID = 'paid';

    public const STATUS_REFUNDED = 'refunded';

    protected $fillable = [
        'managed_file_id',
        'access_token',
        'status',
        'buyer_email',
        'lemon_checkout_id',
        'lemon_order_id',
        'amount_cents',
        'currency',
        'downloads_count',
        'max_downloads',
        'expires_at',
        'paid_at',
    ];

    protected $casts = [
        'amount_cents' => 'integer',
        'downloads_count' => 'integer',
        'max_downloads' => 'integer',
        'expires_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function file(): BelongsTo
    {
        return $this->belongsTo(ManagedFile::class, 'managed_file_id');
    }

    public function getIsPaidAttribute(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function getIsPendingAttribute(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function getIsRefundedAttribute(): bool
    {
        return $this->status === self::STATUS_REFUNDED;
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at !== null && now()->greaterThan($this->expires_at);
    }

    public function getDownloadsExhaustedAttribute(): bool
    {
        return $this->max_downloads !== null && $this->downloads_count >= $this->max_downloads;
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->is_paid && ! $this->is_expired && ! $this->downloads_exhausted;
    }
}
