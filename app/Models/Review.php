<?php

namespace App\Models;

use App\Enums\ReviewSource;
use Database\Factories\ReviewFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * A genuine customer review. Sample reviews (local development only) are
 * never shown when the site runs in production.
 *
 * @property ReviewSource $source
 * @property Carbon $reviewed_at
 */
#[Fillable(['source', 'reviewer_name', 'rating', 'body', 'reviewed_at', 'source_url', 'is_verified', 'repair_job_id', 'service_area_id', 'is_published', 'is_sample'])]
class Review extends Model
{
    /** @use HasFactory<ReviewFactory> */
    use HasFactory, LogsActivity;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'source' => ReviewSource::class,
            'rating' => 'integer',
            'reviewed_at' => 'date',
            'is_verified' => 'boolean',
            'is_published' => 'boolean',
            'is_sample' => 'boolean',
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
     * @return BelongsTo<ServiceArea, $this>
     */
    public function serviceArea(): BelongsTo
    {
        return $this->belongsTo(ServiceArea::class);
    }

    /**
     * Reviews that may be shown on the website: published, newest first, and
     * never samples in production.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function displayable(Builder $query): void
    {
        $query->with('serviceArea')
            ->where('is_published', true)
            ->when(app()->isProduction(), fn (Builder $query) => $query->where('is_sample', false))
            ->orderByDesc('reviewed_at')
            ->orderByDesc('id');
    }

    /**
     * First name and initial only, e.g. "Sarah K.", to respect privacy.
     */
    public function displayName(): string
    {
        $parts = preg_split('/\s+/', trim($this->reviewer_name)) ?: [];
        $first = $parts[0] ?? $this->reviewer_name;
        $last = count($parts) > 1 ? end($parts) : null;

        return $last ? $first.' '.mb_strtoupper(mb_substr($last, 0, 1)).'.' : $first;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['source', 'reviewer_name', 'rating', 'is_published', 'is_verified'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('content');
    }
}
