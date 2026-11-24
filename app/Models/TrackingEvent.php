<?php

namespace App\Models;

use App\Enums\TrackingPlatform;
use App\Enums\TrackingStatus;
use Database\Factories\TrackingEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A server-side conversion sent (or not sent) to an ad or analytics platform.
 *
 * @property TrackingPlatform $platform
 * @property TrackingStatus $status
 * @property array<string, mixed>|null $payload
 * @property Carbon|null $sent_at
 */
#[Fillable(['lead_id', 'repair_job_id', 'event_id', 'event_name', 'platform', 'kind', 'status', 'payload', 'response_code', 'response_body', 'error', 'attempts', 'is_test', 'sent_at'])]
class TrackingEvent extends Model
{
    /** @use HasFactory<TrackingEventFactory> */
    use HasFactory;

    public const string KIND_SERVER = 'server';

    public const string KIND_OFFLINE = 'offline';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'platform' => TrackingPlatform::class,
            'status' => TrackingStatus::class,
            'payload' => 'array',
            'response_code' => 'integer',
            'attempts' => 'integer',
            'is_test' => 'boolean',
            'sent_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Lead, $this>
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * @return BelongsTo<RepairJob, $this>
     */
    public function repairJob(): BelongsTo
    {
        return $this->belongsTo(RepairJob::class);
    }
}
