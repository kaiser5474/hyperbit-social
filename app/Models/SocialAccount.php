<?php

namespace App\Models;

use Database\Factories\SocialAccountFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Crypt;

class SocialAccount extends Model
{
    /** @use HasFactory<SocialAccountFactory> */
    use HasFactory;

    public const PLATFORM_FACEBOOK_PAGE = 'facebook_page';

    public const PLATFORM_INSTAGRAM_BUSINESS = 'instagram_business';

    public const PLATFORM_THREADS = 'threads';

    protected $fillable = [
        'user_id',
        'platform',
        'platform_id',
        'platform_name',
        'platform_username',
        'avatar_url',
        'parent_page_id',
        'access_token',
        'token_expires_at',
        'is_active',
        'last_used_at',
        'last_error',
    ];

    protected $hidden = [
        'access_token',
    ];

    protected function casts(): array
    {
        return [
            'token_expires_at' => 'datetime',
            'last_used_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    protected function accessToken(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value ? Crypt::decryptString($value) : null,
            set: fn (string $value) => Crypt::encryptString($value),
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, 'post_channels')
            ->withPivot(['status', 'platform_post_id', 'permalink', 'error_message', 'published_at'])
            ->withTimestamps();
    }

    public function isFacebookPage(): bool
    {
        return $this->platform === self::PLATFORM_FACEBOOK_PAGE;
    }

    public function isInstagram(): bool
    {
        return $this->platform === self::PLATFORM_INSTAGRAM_BUSINESS;
    }

    public function isTokenExpired(): bool
    {
        if (! $this->token_expires_at) {
            return false;
        }

        return $this->token_expires_at->isPast();
    }

    public function markAsUsed(): void
    {
        $this->update(['last_used_at' => now(), 'last_error' => null]);
    }

    public function markError(string $error): void
    {
        $this->update(['last_error' => $error]);
    }

    public function getPlatformIcon(): string
    {
        return match ($this->platform) {
            self::PLATFORM_FACEBOOK_PAGE => 'facebook',
            self::PLATFORM_INSTAGRAM_BUSINESS => 'instagram',
            self::PLATFORM_THREADS => 'threads',
            default => 'globe',
        };
    }

    public function getPlatformLabel(): string
    {
        return match ($this->platform) {
            self::PLATFORM_FACEBOOK_PAGE => 'Facebook Page',
            self::PLATFORM_INSTAGRAM_BUSINESS => 'Instagram',
            self::PLATFORM_THREADS => 'Threads',
            default => 'Unknown',
        };
    }
}
