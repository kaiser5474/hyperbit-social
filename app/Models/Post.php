<?php

namespace App\Models;

use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Post extends Model
{
    /** @use HasFactory<PostFactory> */
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_PUBLISHING = 'publishing';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_FAILED = 'failed';

    public const STATUS_CANCELLED = 'cancelled';

    public const MAX_RETRIES = 3;

    protected $fillable = [
        'user_id',
        'content',
        'media_path',
        'media_type',
        'media_url',
        'scheduled_at',
        'timezone',
        'status',
        'published_at',
        'retry_count',
        'last_retry_at',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'published_at' => 'datetime',
            'last_retry_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function channels(): BelongsToMany
    {
        return $this->belongsToMany(SocialAccount::class, 'post_channels')
            ->withPivot(['status', 'platform_post_id', 'permalink', 'error_message', 'error_code', 'container_id', 'container_status', 'published_at'])
            ->withTimestamps();
    }

    public function postChannels(): HasMany
    {
        return $this->hasMany(PostChannel::class);
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', self::STATUS_SCHEDULED);
    }

    public function scopeDueForPublishing($query)
    {
        return $query->where(function ($q) {
            $q->where(function ($scheduled) {
                $scheduled->where('status', self::STATUS_SCHEDULED)
                    ->where('scheduled_at', '<=', now());
            })->orWhere(function ($publishing) {
                // Resume in-progress posts after a short grace period so an
                // in-flight worker is not claimed again immediately.
                $publishing->where('status', self::STATUS_PUBLISHING)
                    ->where('updated_at', '<=', now()->subSeconds(45));
            });
        });
    }

    public function scopeForUser($query, User $user)
    {
        return $query->where('user_id', $user->id);
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isScheduled(): bool
    {
        return $this->status === self::STATUS_SCHEDULED;
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function canBeEdited(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_SCHEDULED]);
    }

    public function canBeCancelled(): bool
    {
        return $this->status === self::STATUS_SCHEDULED;
    }

    public function canRetry(): bool
    {
        return $this->status === self::STATUS_FAILED && $this->retry_count < self::MAX_RETRIES;
    }

    public function hasMedia(): bool
    {
        return ! empty($this->media_path);
    }

    public function getPublicMediaUrl(): ?string
    {
        if (! $this->hasMedia()) {
            return null;
        }

        return $this->media_url ?: url('storage/'.$this->media_path);
    }

    public function markAsPublishing(): void
    {
        $this->update(['status' => self::STATUS_PUBLISHING]);
    }

    public function markAsPublished(): void
    {
        $this->update([
            'status' => self::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);
    }

    public function markAsFailed(): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'retry_count' => $this->retry_count + 1,
            'last_retry_at' => now(),
        ]);
    }

    public function cancel(): void
    {
        $this->update(['status' => self::STATUS_CANCELLED]);
    }

    public function getScheduledAtInUserTimezone(): ?string
    {
        if (! $this->scheduled_at) {
            return null;
        }

        return $this->scheduled_at->setTimezone($this->timezone)->format('Y-m-d\TH:i');
    }

    public function getStatusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'bg-gray-100 text-gray-800',
            self::STATUS_SCHEDULED => 'bg-blue-100 text-blue-800',
            self::STATUS_PUBLISHING => 'bg-yellow-100 text-yellow-800',
            self::STATUS_PUBLISHED => 'bg-green-100 text-green-800',
            self::STATUS_FAILED => 'bg-red-100 text-red-800',
            self::STATUS_CANCELLED => 'bg-gray-100 text-gray-500',
            default => 'bg-gray-100 text-gray-800',
        };
    }
}
