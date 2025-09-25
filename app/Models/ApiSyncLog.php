<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiSyncLog extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'sync_type',
        'status',
        'records_processed',
        'records_success',
        'records_failed',
        'error_message',
        'sync_started_at',
        'sync_completed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'records_processed' => 'integer',
        'records_success' => 'integer',
        'records_failed' => 'integer',
        'sync_started_at' => 'datetime',
        'sync_completed_at' => 'datetime',
    ];

    /**
     * Sync type constants.
     */
    public const SYNC_TYPE_EQUIPMENT = 'equipment';
    public const SYNC_TYPE_RUNNING_TIME = 'running_time';
    public const SYNC_TYPE_WORK_ORDER = 'work_order';
    public const SYNC_TYPE_FULL = 'full';

    /**
     * Status constants.
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_RUNNING = 'running';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Scope a query to filter by sync type.
     */
    public function scopeBySyncType($query, $syncType)
    {
        return $query->where('sync_type', $syncType);
    }

    /**
     * Scope a query to filter by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to get recent logs.
     */
    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope a query to get failed logs.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope a query to get completed logs.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Helper to create a new running log.
     */
    public static function start(string $type): self
    {
        return static::create([
            'sync_type' => $type,
            'status' => self::STATUS_RUNNING,
            'records_processed' => 0,
            'records_success' => 0,
            'records_failed' => 0,
            'sync_started_at' => now(),
        ]);
    }

    /**
     * Mark log as completed successfully.
     */
    public function finishSuccess(int $processed, int $success, int $failed): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'records_processed' => $processed,
            'records_success' => $success,
            'records_failed' => $failed,
            'sync_completed_at' => now(),
        ]);
    }

    /**
     * Mark log as failed with message.
     */
    public function finishFailed(string $message): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $message,
            'sync_completed_at' => now(),
        ]);
    }

    /**
     * Get the success rate as a percentage.
     */
    public function getSuccessRateAttribute(): float
    {
        if ($this->records_processed === 0) {
            return 0;
        }

        return ($this->records_success / $this->records_processed) * 100;
    }

    /**
     * Get the duration of the sync in seconds.
     */
    public function getDurationAttribute(): ?int
    {
        if (!$this->sync_started_at || !$this->sync_completed_at) {
            return null;
        }

        return $this->sync_completed_at->diffInSeconds($this->sync_started_at);
    }

    /**
     * Get validation rules for the API sync log.
     *
     * @return array<string, mixed>
     */
    public static function validationRules(): array
    {
        return [
            'sync_type' => 'required|in:equipment,running_time,work_order,full',
            'status' => 'required|in:pending,running,completed,failed,cancelled',
            'records_processed' => 'integer|min:0',
            'records_success' => 'integer|min:0',
            'records_failed' => 'integer|min:0',
            'error_message' => 'nullable|string',
            'sync_started_at' => 'nullable|date',
            'sync_completed_at' => 'nullable|date|after_or_equal:sync_started_at',
        ];
    }
}
