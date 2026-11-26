<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Crypt;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'facebook_id',
        'facebook_token',
        'facebook_token_expires_at',
        'avatar',
        'timezone',
        'locale',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'facebook_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'facebook_token_expires_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected function facebookToken(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value ? Crypt::decryptString($value) : null,
            set: function (?string $value) {
                if ($value === null || $value === '') {
                    return $value;
                }

                return Crypt::encryptString($value);
            },
        );
    }

    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function activeSocialAccounts(): HasMany
    {
        return $this->socialAccounts()->where('is_active', true);
    }

    public function facebookPages(): HasMany
    {
        return $this->socialAccounts()->where('platform', 'facebook_page');
    }

    public function instagramAccounts(): HasMany
    {
        return $this->socialAccounts()->where('platform', 'instagram_business');
    }
}
