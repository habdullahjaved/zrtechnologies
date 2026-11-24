<?php

namespace App\Models;

use App\Models\Concerns\HasFaqs;
use App\Models\Concerns\HasSeoMeta;
use App\Models\Concerns\RedirectsOnSlugChange;
use App\Models\Contracts\HasPublicPage;
use App\Services\Seo\ContentQuality;
use App\Settings\SiteSettings;
use App\Support\Money;
use Database\Factories\ServiceAreaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * A town we collect from and return to.
 *
 * @property list<string> $postcode_districts
 * @property string|null $centre_lat
 * @property string|null $centre_lng
 * @property string|null $radius_km
 * @property string $similarity_score
 * @property list<array{type: string, data: array<string, mixed>}>|null $blocks
 */
#[Fillable(['name', 'slug', 'town', 'county', 'postcode_districts', 'centre_lat', 'centre_lng', 'radius_km', 'collection_fee_pence', 'typical_collection_times', 'summary', 'intro', 'blocks', 'landmarks', 'is_active', 'is_draft', 'sort'])]
class ServiceArea extends Model implements HasPublicPage
{
    /** @use HasFactory<ServiceAreaFactory> */
    use HasFactory, HasFaqs, HasSeoMeta, LogsActivity, RedirectsOnSlugChange;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'postcode_districts' => 'array',
            'centre_lat' => 'decimal:6',
            'centre_lng' => 'decimal:6',
            'radius_km' => 'decimal:1',
            'collection_fee_pence' => 'integer',
            'is_active' => 'boolean',
            'is_draft' => 'boolean',
            'blocks' => 'array',
            'unique_word_count' => 'integer',
            'similarity_score' => 'decimal:2',
        ];
    }

    /**
     * Active areas in display order.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function listed(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('sort')->orderBy('name');
    }

    protected static function booted(): void
    {
        static::saved(function (self $area): void {
            if ($area->wasChanged(['intro', 'blocks', 'landmarks', 'summary']) || $area->wasRecentlyCreated) {
                app(ContentQuality::class)->refreshServiceAreas();
            }
        });
    }

    /**
     * @return HasMany<Review, $this>
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function url(): string
    {
        return route('locations.show', $this->slug);
    }

    public function pathForSlug(string $slug): ?string
    {
        return '/iphone-repair-'.$slug;
    }

    public function isPubliclyVisible(): bool
    {
        return $this->is_active;
    }

    /**
     * Enough unique local content, not too similar to another town's page.
     */
    public function passesContentChecks(): bool
    {
        return app(ContentQuality::class)->passes(
            $this->unique_word_count,
            (float) $this->similarity_score,
            app(SiteSettings::class)->min_unique_words_location,
        );
    }

    public function coversDistrict(string $outcode): bool
    {
        return in_array(strtoupper($outcode), $this->postcode_districts, true);
    }

    /**
     * Whether a point lies within this area's collection radius.
     */
    public function coversPoint(float $latitude, float $longitude): bool
    {
        if ($this->radius_km === null || $this->centre_lat === null || $this->centre_lng === null) {
            return false;
        }

        return self::distanceKm((float) $this->centre_lat, (float) $this->centre_lng, $latitude, $longitude) <= (float) $this->radius_km;
    }

    public function collectionFeeLabel(): string
    {
        return $this->collection_fee_pence === 0 ? 'Free' : Money::format($this->collection_fee_pence);
    }

    /**
     * Great-circle distance between two points (haversine formula).
     */
    public static function distanceKm(float $fromLat, float $fromLng, float $toLat, float $toLng): float
    {
        $earthRadiusKm = 6371.0;

        $latDelta = deg2rad($toLat - $fromLat);
        $lngDelta = deg2rad($toLng - $fromLng);

        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($fromLat)) * cos(deg2rad($toLat)) * sin($lngDelta / 2) ** 2;

        return $earthRadiusKm * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'slug', 'postcode_districts', 'centre_lat', 'centre_lng', 'radius_km', 'collection_fee_pence', 'is_active', 'is_draft'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('coverage');
    }
}
