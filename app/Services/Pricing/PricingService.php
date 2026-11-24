<?php

namespace App\Services\Pricing;

use App\Models\DeviceModel;
use App\Models\RepairPrice;
use App\Models\RepairType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

/**
 * The single source of repair prices for the website (and, later, the booking
 * wizard, schema offers and the chatbot). Prices only ever come from the database.
 */
class PricingService
{
    /**
     * SQL for the price a customer pays: the sale price when set and lower.
     */
    private const string EFFECTIVE_PRICE_SQL = 'CASE WHEN sale_price_pence IS NOT NULL AND sale_price_pence < price_pence THEN sale_price_pence ELSE price_pence END';

    /**
     * Active models that have at least one price customers can see, newest first.
     *
     * @return Collection<int, DeviceModel>
     */
    public function modelsWithPrices(): Collection
    {
        return DeviceModel::query()
            ->listed()
            ->whereHas('prices', fn (Builder $prices) => $prices->available())
            ->get();
    }

    /**
     * Every price customers can see for a model, grouped by repair type in
     * display order, each group ordered by part quality.
     *
     * @return SupportCollection<int, Collection<int, RepairPrice>> keyed by repair type id
     */
    public function pricesForModel(DeviceModel $model): SupportCollection
    {
        return $this->groupByRepair(RepairPrice::query()
            ->available()
            ->where('device_model_id', $model->getKey())
            ->with(['repairType', 'partQuality'])
            ->get());
    }

    /**
     * The full public price list in one query: model id => prices grouped by repair type.
     *
     * @return array<int, SupportCollection<int, Collection<int, RepairPrice>>>
     */
    public function priceList(): array
    {
        return RepairPrice::query()
            ->available()
            ->with(['repairType', 'partQuality'])
            ->get()
            ->groupBy('device_model_id')
            ->map(fn (Collection $prices): SupportCollection => $this->groupByRepair($prices))
            ->all();
    }

    /**
     * @param  Collection<int, RepairPrice>  $prices
     * @return SupportCollection<int, Collection<int, RepairPrice>>
     */
    private function groupByRepair(Collection $prices): SupportCollection
    {
        $prices = $prices
            ->sortBy([
                fn (RepairPrice $a, RepairPrice $b): int => [$a->repairType->sort, $a->repairType->name] <=> [$b->repairType->sort, $b->repairType->name],
                fn (RepairPrice $a, RepairPrice $b): int => ($a->partQuality->sort ?? 0) <=> ($b->partQuality->sort ?? 0),
            ]);

        return $prices
            ->groupBy('repair_type_id')
            ->toBase();
    }

    /**
     * The lowest price customers can see for each repair type ("from £…").
     *
     * @return array<int, int> repair type id => pence
     */
    public function fromPrices(): array
    {
        return RepairPrice::query()
            ->available()
            ->selectRaw('repair_type_id, MIN('.self::EFFECTIVE_PRICE_SQL.') as from_pence')
            ->groupBy('repair_type_id')
            ->pluck('from_pence', 'repair_type_id')
            ->map(fn (mixed $pence): int => (int) $pence)
            ->all();
    }

    public function fromPrice(RepairType $repairType): ?int
    {
        return $this->fromPrices()[$repairType->getKey()] ?? null;
    }

    /**
     * Active repair types that have at least one visible price, in display order,
     * each with its "from" price in pence.
     *
     * @return list<array{repair: RepairType, from_pence: int}>
     */
    public function repairsWithFromPrices(): array
    {
        $fromPrices = $this->fromPrices();

        return RepairType::query()
            ->listed()
            ->whereIn('id', array_keys($fromPrices))
            ->get()
            ->map(fn (RepairType $repair): array => ['repair' => $repair, 'from_pence' => $fromPrices[$repair->getKey()]])
            ->values()
            ->all();
    }

    public function draftPriceCount(): int
    {
        return RepairPrice::query()->where('is_draft', true)->count();
    }
}
