<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramAuthContact extends Model
{
    protected $fillable = [
        'telegram_user_id',
        'phone',
        'first_name',
        'username',
    ];
}
