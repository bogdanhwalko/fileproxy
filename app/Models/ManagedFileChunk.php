<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManagedFileChunk extends Model
{
    use HasFactory;

    protected $fillable = [
        'managed_file_id',
        'sequence',
        'telegram_storage_group_id',
        'telegram_bot_token_id',
        'telegram_chat_id',
        'telegram_message_id',
        'telegram_file_id',
        'telegram_file_unique_id',
        'iv',
        'auth_tag',
        'encrypted_size',
        'plaintext_size',
    ];

    protected $casts = [
        'sequence' => 'integer',
        'telegram_message_id' => 'integer',
        'encrypted_size' => 'integer',
        'plaintext_size' => 'integer',
    ];

    public function file(): BelongsTo
    {
        return $this->belongsTo(ManagedFile::class, 'managed_file_id');
    }

    public function telegramBotToken(): BelongsTo
    {
        return $this->belongsTo(TelegramBotToken::class);
    }

    public function telegramStorageGroup(): BelongsTo
    {
        return $this->belongsTo(TelegramStorageGroup::class);
    }
}
