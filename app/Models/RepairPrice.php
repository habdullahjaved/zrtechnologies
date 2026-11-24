<?php

namespace App\Models;

use App\Support\Money;
use Database\Factories\RepairPriceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * One cell of the price matrix: what a repair costs for a model and part quality.
 *
 * Draft prices ("is_draft") are placeholders the owner must confirm before launch.
 *
 * @property int $price_pence
 * @property int|null $sale_price_pence
 */
#[Fillable(['device_model_id', 'repair_type_id', 'part_quality_id', 'price_pence', 'sale_price_pence', 'warranty_days', 'in_stock', 'is_active', 'is_draft'])]
class RepairPrice extends Model
{
    /** @use HasFactory<RepairPriceFactory> */
    use HasFactory, LogsActivity;

    /**
     * Defaults mirrored from the migration so new, unsaved models behave the same.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'warranty_days' => 90,
        'in_stock' => true,
        'is_active' => true,
        'is_draft' => true,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price_pence' => 'integer',
            'sale_price_pence' => 'integer',
            'warranty_days' => 'integer',
            'in_stock' => 'boolean',
            'is_active' => 'boolean',
            'is_draft' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<DeviceModel, $this>
     */
    public function deviceModel(): BelongsTo
    {
        return $this->belongsTo(DeviceModel::class);
    }

    /**
     * @return BelongsTo<RepairType, $this>
     */
    public function repairType(): BelongsTo
    {
        return $this->belongsTo(RepairType::class);
    }

    /**
     * @return BelongsTo<PartQuality, $this>
     */
    public function partQuality(): BelongsTo
    {
        return $this->belongsTo(PartQuality::class);
    }

    /**
     * Prices customers may see: the price, its model and its repair are all switched on.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function available(Builder $query): void
    {
        $query->where('repair_prices.is_active', true)
            ->whereHas('deviceModel', fn (Builder $model) => $model->where('is_active', true))
            ->whereHas('repairType', fn (Builder $repair) => $repair->where('is_active', true))
            ->where(fn (Builder $quality) => $quality
                ->whereNull('part_quality_id')
                ->orWhereHas('partQuality', fn (Builder $grade) => $grade->where('is_active', true)));
    }

    /**
     * The price in pounds ("59.00") for admin forms and inline editing; stored as pence.
     *
     * @return Attribute<string, string|int|float|null>
     */
    protected function pricePounds(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes): string => Money::toPounds((int) ($attributes['price_pence'] ?? 0)),
            set: fn (string|int|float|null $value): array => ['price_pence' => Money::parsePounds($value) ?? 0],
        );
    }

    /**
     * The optional sale price in pounds; an empty value removes the sale.
     *
     * @return Attribute<string|null, string|int|float|null>
     */
    protected function salePricePounds(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes): ?string => isset($attributes['sale_price_pence']) ? Money::toPounds((int) $attributes['sale_price_pence']) : null,
            set: fn (string|int|float|null $value): array => ['sale_price_pence' => blank($value) ? null : Money::parsePounds($value)],
        );
    }

    /**
     * e.g. "iPhone 15 – Screen Replacement (Premium)". Needs deviceModel, repairType and partQuality loaded.
     */
    public function label(): string
    {
        $quality = $this->partQuality !== null ? ' ('.$this->partQuality->name.')' : '';

        return $this->deviceModel->name.' – '.$this->repairType->name.$quality;
    }

    public function isOnSale(): bool
    {
        return $this->sale_price_pence !== null && $this->sale_price_pence < $this->price_pence;
    }

    /**
     * What the customer pays: the sale price when one is set and lower, otherwise the price.
     */
    public function effectivePricePence(): int
    {
        return $this->isOnSale() ? (int) $this->sale_price_pence : $this->price_pence;
    }

    public function formattedPrice(): string
    {
        return Money::format($this->effectivePricePence());
    }

    /**
     * Warranty in words: 365 → "12 months", 180 → "6 months", 45 → "45 days", 0 → null.
     */
    public function warrantyLabel(): ?string
    {
        $days = (int) $this->warranty_days;

        if ($days === 0) {
            return null;
        }

        $months = match (true) {
            $days % 365 === 0 => intdiv($days, 365) * 12,
            $days % 30 === 0 => intdiv($days, 30),
            default => null,
        };

        if ($months !== null) {
            return $months.' '.($months === 1 ? 'month' : 'months');
        }

        return $days.' '.($days === 1 ? 'day' : 'days');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['price_pence', 'sale_price_pence', 'warranty_days', 'in_stock', 'is_active', 'is_draft'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('prices');
    }
}
