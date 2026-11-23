<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DataDeletionRequest extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'confirmation_code',
        'facebook_user_id',
        'status',
        'deleted_data',
        'requested_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'deleted_data' => 'array',
            'requested_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public static function createForUser(string $facebookUserId): self
    {
        return self::create([
            'confirmation_code' => Str::uuid()->toString(),
            'facebook_user_id' => $facebookUserId,
            'status' => self::STATUS_PENDING,
            'requested_at' => now(),
        ]);
    }

    public function markAsProcessing(): void
    {
        $this->update(['status' => self::STATUS_PROCESSING]);
    }

    public function markAsCompleted(array $deletedData): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'deleted_data' => $deletedData,
            'completed_at' => now(),
        ]);
    }

    public function getStatusUrl(): string
    {
        return url('/data-deletion/status/'.$this->confirmation_code);
    }
}
