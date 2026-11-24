<?php

namespace App\Models;

use Database\Factories\RepairTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * A repair we offer, e.g. Screen Replacement. Switching it off hides it from
 * the price checker and (later) the booking wizard and its page.
 */
#[Fillable(['service_category_id', 'name', 'slug', 'short_name', 'summary', 'icon', 'typical_duration_minutes', 'requires_diagnosis', 'is_active', 'is_popular', 'sort'])]
class RepairType extends Model
{
    /** @use HasFactory<RepairTypeFactory> */
    use HasFactory, LogsActivity;

    /** The "not sure what's wrong" option in the booking wizard. */
    public const string DIAGNOSTICS = 'diagnostics';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'typical_duration_minutes' => 'integer',
            'requires_diagnosis' => 'boolean',
            'is_active' => 'boolean',
            'is_popular' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<ServiceCategory, $this>
     */
    public function serviceCategory(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class);
    }

    /**
     * @return HasMany<RepairPrice, $this>
     */
    public function prices(): HasMany
    {
        return $this->hasMany(RepairPrice::class);
    }

    /**
     * Active repairs in display order.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function listed(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('sort')->orderBy('name');
    }

    /**
     * Short label for tight spaces such as table headers and chips.
     */
    public function displayName(): string
    {
        return $this->short_name ?: $this->name;
    }

    /**
     * Human-readable typical repair time, e.g. "about 45 minutes" or "about 2 hours".
     */
    public function typicalDuration(): ?string
    {
        $minutes = $this->typical_duration_minutes;

        if ($minutes === null) {
            return null;
        }

        if ($minutes < 60) {
            return "about {$minutes} minutes";
        }

        $hours = round($minutes / 60, 1);
        $formatted = rtrim(rtrim(number_format($hours, 1), '0'), '.');

        return 'about '.$formatted.' '.($hours == 1.0 ? 'hour' : 'hours');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'slug', 'short_name', 'summary', 'requires_diagnosis', 'is_active', 'is_popular'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('catalogue');
    }
}
