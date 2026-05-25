<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'phone',
        'email',
        'password',
        'is_admin',
        'is_blocked',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_admin' => 'boolean',
        'is_blocked' => 'boolean',
        'password' => 'hashed',
    ];

    public function files(): HasMany
    {
        return $this->hasMany(ManagedFile::class);
    }

    public function folders(): HasMany
    {
        return $this->hasMany(FileFolder::class);
    }

    public function tags(): HasMany
    {
        return $this->hasMany(\App\Models\Tag::class);
    }

    public function telegramBotTokens(): HasMany
    {
        return $this->hasMany(TelegramBotToken::class);
    }

    public function telegramStorageGroups(): HasMany
    {
        return $this->hasMany(TelegramStorageGroup::class);
    }
}
