<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeedbackMessage extends Model
{
    use HasFactory;

    public const TYPES = [
        'idea'     => 'Ідея',
        'bug'      => 'Баг',
        'question' => 'Питання',
        'other'    => 'Інше',
    ];

    public const STATUSES = [
        'new'      => 'Нове',
        'read'     => 'Прочитано',
        'resolved' => 'Вирішено',
    ];

    protected $fillable = [
        'user_id',
        'type',
        'subject',
        'message',
        'contact',
        'status',
        'admin_notes',
        'user_agent',
        'ip',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
