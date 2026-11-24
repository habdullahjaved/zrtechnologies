<?php

namespace App\Models;

use App\Enums\DisplayType;
use App\Models\Concerns\HasFaqs;
use App\Models\Concerns\HasSeoMeta;
use App\Models\Concerns\RedirectsOnSlugChange;
use App\Models\Contracts\HasPublicPage;
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
 * @property list<array{type: string, data: array<string, mixed>}>|null $blocks
 */
#[Fillable(['device_brand_id', 'name', 'slug', 'family', 'release_year', 'display_type', 'notes', 'intro', 'blocks', 'is_active', 'is_popular', 'sort'])]
class DeviceModel extends Model implements HasPublicPage
{
    /** @use HasFactory<DeviceModelFactory> */
    use HasFactory, HasFaqs, HasSeoMeta, LogsActivity, RedirectsOnSlugChange;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'display_type' => DisplayType::class,
            'blocks' => 'array',
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

    /**
     * @return HasMany<ModelRepairPage, $this>
     */
    public function repairPages(): HasMany
    {
        return $this->hasMany(ModelRepairPage::class);
    }

    public function url(): string
    {
        return route('repairs.show', $this->slug);
    }

    public function pathForSlug(string $slug): ?string
    {
        return '/iphone-repair/'.$slug;
    }

    public function isPubliclyVisible(): bool
    {
        return $this->is_active && $this->prices()->available()->exists();
    }

    /**
     * Touch ID models (iPhone 8 and SE) have no Face ID.
     */
    public function hasFaceId(): bool
    {
        return ! str_starts_with($this->name, 'iPhone 8') && ! str_starts_with($this->name, 'iPhone SE');
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
