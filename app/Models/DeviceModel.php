<?php

namespace App\Models;

use App\Enums\DisplayType;
use Database\Factories\DeviceModelFactory;
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
 * A phone model we repair, e.g. iPhone 15 Pro Max.
 *
 * Lower "sort" values come first; the seeder lists newest models first.
 *
 * @property DisplayType|null $display_type
 */
#[Fillable(['device_brand_id', 'name', 'slug', 'family', 'release_year', 'display_type', 'notes', 'is_active', 'is_popular', 'sort'])]
class DeviceModel extends Model
{
    /** @use HasFactory<DeviceModelFactory> */
    use HasFactory, LogsActivity;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'display_type' => DisplayType::class,
            'release_year' => 'integer',
            'is_active' => 'boolean',
            'is_popular' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<DeviceBrand, $this>
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(DeviceBrand::class, 'device_brand_id');
    }

    /**
     * @return HasMany<RepairPrice, $this>
     */
    public function prices(): HasMany
    {
        return $this->hasMany(RepairPrice::class);
    }

    /**
     * Active models in display order (newest first).
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function listed(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('sort')->orderBy('name');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'slug', 'family', 'release_year', 'display_type', 'is_active', 'is_popular'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('catalogue');
    }
}
