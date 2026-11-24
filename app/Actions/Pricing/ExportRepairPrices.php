<?php

namespace App\Actions\Pricing;

use App\Models\DeviceModel;
use App\Models\RepairPrice;
use App\Models\RepairType;
use App\Support\Money;

/**
 * Writes the whole price list as CSV (opens in Excel or Google Sheets). The
 * same columns are read back by ImportRepairPrices.
 */
class ExportRepairPrices
{
    /**
     * @var list<string>
     */
    public const array COLUMNS = [
        'model_slug', 'model', 'repair_slug', 'repair', 'quality_slug', 'quality',
        'price', 'sale_price', 'warranty_days', 'in_stock', 'active', 'draft',
    ];

    /**
     * @param  resource  $stream
     */
    public function handle($stream): void
    {
        fputcsv($stream, self::COLUMNS, escape: '');

        RepairPrice::query()
            ->with(['deviceModel', 'repairType', 'partQuality'])
            ->orderBy(DeviceModel::query()->select('sort')->whereColumn('device_models.id', 'repair_prices.device_model_id'))
            ->orderBy(RepairType::query()->select('sort')->whereColumn('repair_types.id', 'repair_prices.repair_type_id'))
            ->orderBy('part_quality_key')
            ->lazy(200)
            ->each(function (RepairPrice $price) use ($stream): void {
                fputcsv($stream, [
                    $price->deviceModel->slug,
                    $price->deviceModel->name,
                    $price->repairType->slug,
                    $price->repairType->name,
                    $price->partQuality->slug ?? '',
                    $price->partQuality->name ?? '',
                    Money::toPounds($price->price_pence),
                    $price->sale_price_pence !== null ? Money::toPounds($price->sale_price_pence) : '',
                    $price->warranty_days,
                    $price->in_stock ? 'yes' : 'no',
                    $price->is_active ? 'yes' : 'no',
                    $price->is_draft ? 'yes' : 'no',
                ], escape: '');
            });
    }

    public function filename(): string
    {
        return 'zr-repair-prices-'.now()->format('Y-m-d').'.csv';
    }
}
