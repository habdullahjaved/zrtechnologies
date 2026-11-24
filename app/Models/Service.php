<?php

namespace App\Models;

use App\Models\Concerns\HasFaqs;
use App\Models\Concerns\HasSeoMeta;
use App\Models\Concerns\RedirectsOnSlugChange;
use App\Models\Contracts\HasPublicPage;
use App\Support\Content\Blocks;
use Database\Factories\ServiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * A service page, e.g. Screen Replacement at /iphone-repair/screen-replacement.
 * It covers one or more repair types and is shown while at least one of them
 * is offered.
 *
 * @property list<array{type: string, data: array<string, mixed>}>|null $blocks
 */
#[Fillable(['service_category_id', 'name', 'slug', 'heading', 'summary', 'intro', 'blocks', 'icon', 'og_image', 'is_active', 'sort'])]
class Service extends Model implements HasPublicPage
{
    /** @use HasFactory<ServiceFactory> */
    use HasFactory, HasFaqs, HasSeoMeta, LogsActivity, RedirectsOnSlugChange;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'blocks' => 'array',
            'is_active' => 'boolean',
            'word_count' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(fn (self $service) => $service->fillDerivedAttributes());
    }

    /**
     * Words of the page's own content (introduction and blocks).
     */
    public function fillDerivedAttributes(): void
    {
        $this->word_count = Blocks::wordCount(Blocks::plainText($this->blocks).' '.$this->intro);
    }

    /**
     * @return BelongsTo<ServiceCategory, $this>
     */
    public function serviceCategory(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class);
    }

    /**
     * @return BelongsToMany<RepairType, $this>
     */
    public function repairTypes(): BelongsToMany
    {
        return $this->belongsToMany(RepairType::class)
            ->withPivot('sort')
            ->orderByPivot('sort');
    }

    /**
     * @return HasMany<Post, $this>
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    /**
     * @return HasMany<Video, $this>
     */
    public function videos(): HasMany
    {
        return $this->hasMany(Video::class);
    }

    /**
     * Active iPhone repair service pages with at least one offered repair, in display order.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function listed(Builder $query): void
    {
        $query->where('is_active', true)
            ->whereHas('serviceCategory', fn (Builder $category) => $category->where('slug', ServiceCategory::IPHONE_REPAIR)->where('is_active', true))
            ->where(fn (Builder $query) => $query
                ->whereDoesntHave('repairTypes')
                ->orWhereHas('repairTypes', fn (Builder $repairs) => $repairs->where('is_active', true)))
            ->orderBy('sort')
            ->orderBy('name');
    }

    public function heading(): string
    {
        return $this->heading ?: "iPhone {$this->name} in Blackburn";
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
        return self::query()->listed()->whereKey($this->getKey())->exists();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'slug', 'heading', 'summary', 'is_active'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('content');
    }
}
