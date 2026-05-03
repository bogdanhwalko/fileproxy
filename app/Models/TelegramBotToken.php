<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TelegramBotToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'username',
        'token',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'token' => 'encrypted',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function storageGroups(): HasMany
    {
        return $this->hasMany(TelegramStorageGroup::class);
    }

    public function getMaskedTokenAttribute(): string
    {
        $token = (string) $this->token;

        if (strlen($token) <= 10) {
            return str_repeat('*', max(strlen($token), 6));
        }

        return substr($token, 0, 6).'...'.substr($token, -4);
    }
}
