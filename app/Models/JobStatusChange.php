<?php

namespace App\Models;

use App\Enums\JobStatus;
use Database\Factories\JobStatusChangeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One entry in a repair job's status history. Written once, never edited.
 *
 * @property JobStatus|null $from_status
 * @property JobStatus $to_status
 * @property string|null $note
 * @property string|null $customer_message
 * @property bool $customer_notified
 * @property Carbon $created_at
 */
#[Table('job_status_history')]
#[Fillable(['repair_job_id', 'from_status', 'to_status', 'note', 'customer_message', 'user_id', 'customer_notified'])]
class JobStatusChange extends Model
{
    /** @use HasFactory<JobStatusChangeFactory> */
    use HasFactory;

    /** History rows are never updated. */
    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'from_status' => JobStatus::class,
            'to_status' => JobStatus::class,
            'customer_notified' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<RepairJob, $this>
     */
    public function repairJob(): BelongsTo
    {
        return $this->belongsTo(RepairJob::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
