<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TelegramStorageGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'telegram_bot_token_id',
        'title',
        'chat_id',
        'is_default',
        'is_global_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_global_default' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function botToken(): BelongsTo
    {
        return $this->belongsTo(TelegramBotToken::class, 'telegram_bot_token_id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(ManagedFile::class);
    }
}
