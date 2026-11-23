<?php

namespace App\Models;

use Database\Factories\PostChannelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostChannel extends Model
{
    /** @use HasFactory<PostChannelFactory> */
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_PUBLISHING = 'publishing';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'post_id',
        'social_account_id',
        'status',
        'platform_post_id',
        'permalink',
        'error_message',
        'error_code',
        'container_id',
        'container_status',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function socialAccount(): BelongsTo
    {
        return $this->belongsTo(SocialAccount::class);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isPublishing(): bool
    {
        return $this->status === self::STATUS_PUBLISHING;
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function markAsPublishing(): void
    {
        $this->update(['status' => self::STATUS_PUBLISHING]);
    }

    public function markAsPublished(string $platformPostId, ?string $permalink = null): void
    {
        $this->update([
            'status' => self::STATUS_PUBLISHED,
            'platform_post_id' => $platformPostId,
            'permalink' => $permalink,
            'published_at' => now(),
            'error_message' => null,
            'error_code' => null,
        ]);
    }

    public function markAsFailed(string $message, ?string $code = null): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $message,
            'error_code' => $code,
        ]);
    }

    public function setContainer(string $containerId, string $status): void
    {
        $this->update([
            'container_id' => $containerId,
            'container_status' => $status,
        ]);
    }
}
