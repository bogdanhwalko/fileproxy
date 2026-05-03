<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhoneAuthChallenge extends Model
{
    use HasFactory;

    protected $fillable = [
        'phone',
        'token',
        'code_hash',
        'attempts',
        'expires_at',
        'consumed_at',
    ];

    protected $casts = [
        'attempts' => 'integer',
        'expires_at' => 'datetime',
        'consumed_at' => 'datetime',
    ];
}
