<?php

use App\Actions\Pricing\AdjustRepairPrices;
use App\Actions\Pricing\ExportRepairPrices;
use App\Actions\Pricing\ImportRepairPrices;
use App\Models\DeviceModel;
use App\Models\PartQuality;
use App\Models\RepairPrice;
use App\Models\RepairType;

/**
 * Write CSV rows to a temporary file and return its path.
 *
 * @param  list<list<string>>  $rows
 */
function priceCsv(array $rows): string
{
    $path = tempnam(sys_get_temp_dir(), 'prices');
    $handle = fopen($path, 'wb');

    foreach ($rows as $row) {
        fputcsv($handle, $row, escape: '');
    }

    fclose($handle);

    return $path;
}

beforeEach(function () {
    $this->model = DeviceModel::factory()->create(['name' => 'iPhone 15', 'slug' => 'iphone-15']);
    $this->screen = RepairType::factory()->create(['name' => 'Screen Replacement', 'slug' => 'screen-replacement']);
    $this->battery = RepairType::factory()->create(['name' => 'Battery Replacement', 'slug' => 'battery-replacement']);
    $this->premium = PartQuality::factory()->create(['name' => 'Premium', 'slug' => 'premium']);

    $this->screenPrice = RepairPrice::factory()->for($this->model)->for($this->screen)->for($this->premium)->draft()->create(['price_pence' => 17900]);
    $this->batteryPrice = RepairPrice::factory()->for($this->model)->for($this->battery)->draft()->create(['price_pence' => 7500, 'warranty_days' => 365]);
});

it('exports the price list as CSV that imports back unchanged', function () {
    $stream = fopen('php://temp', 'w+b');
    app(ExportRepairPrices::class)->handle($stream);
    rewind($stream);
    $csv = stream_get_contents($stream);

    expect($csv)->toContain('model_slug,model,repair_slug,repair,quality_slug,quality,price,sale_price,warranty_days,in_stock,active,draft')
        ->toContain('iphone-15,"iPhone 15",screen-replacement,"Screen Replacement",premium,Premium,179.00,,90,yes,yes,yes')
        ->toContain('iphone-15,"iPhone 15",battery-replacement,"Battery Replacement",,,75.00,,365,yes,yes,yes');

    $path = tempnam(sys_get_temp_dir(), 'prices');
    file_put_contents($path, $csv);
    $result = app(ImportRepairPrices::class)->handle($path);

    expect($result->failed())->toBeFalse()
        ->and($result->unchanged)->toBe(2)
        ->and($result->updated)->toBe(0);
});

it('updates and adds prices from a CSV, leaving blank optional cells alone', function () {
    $model = DeviceModel::factory()->create(['slug' => 'iphone-16']);

    $result = app(ImportRepairPrices::class)->handle(priceCsv([
        ["\u{FEFF}model_slug", 'repair_slug', 'quality_slug', 'price', 'sale_price', 'warranty_days', 'draft'],
        ['iphone-15', 'screen-replacement', 'premium', '£189.00', '169', '', 'no'],
        ['iphone-15', 'battery-replacement', '', '75', '', '', ''],
        ['iphone-16', 'battery-replacement', '', '79.99', '', '180', 'yes'],
    ]));

    expect($result->failed())->toBeFalse()
        ->and($result->summary())->toBe('1 updated, 1 added, 1 unchanged.');

    $this->screenPrice->refresh();
    expect($this->screenPrice->price_pence)->toBe(18900)
        ->and($this->screenPrice->sale_price_pence)->toBe(16900)
        ->and($this->screenPrice->warranty_days)->toBe(90)
        ->and($this->screenPrice->is_draft)->toBeFalse();

    $added = RepairPrice::query()->whereBelongsTo($model)->sole();
    expect($added->price_pence)->toBe(7999)
        ->and($added->warranty_days)->toBe(180)
        ->and($added->is_draft)->toBeTrue();
});

it('saves nothing when any row has a problem, and says which lines', function () {
    $result = app(ImportRepairPrices::class)->handle(priceCsv([
        ['model_slug', 'repair_slug', 'quality_slug', 'price', 'draft'],
        ['iphone-15', 'battery-replacement', '', '99', 'yes'],
        ['iphone-404', 'battery-replacement', '', '99', 'yes'],
        ['iphone-15', 'screen-replacement', 'gold', 'cheap', 'maybe'],
    ]));

    expect($result->failed())->toBeTrue()
        ->and($result->errors)->toBe([
            'Line 3: unknown model "iphone-404".',
            'Line 4: unknown part quality "gold"; price must be an amount in pounds, e.g. 59.99; draft must be yes or no.',
        ])
        ->and($this->batteryPrice->fresh()->price_pence)->toBe(7500);
});

it('refuses a file without the required columns', function () {
    $result = app(ImportRepairPrices::class)->handle(priceCsv([['model', 'cost'], ['iPhone 15', '10']]));

    expect($result->errors[0])->toContain('missing these columns: model_slug, repair_slug, price');
});

it('changes prices by a percentage with the chosen rounding', function (float $percent, string $rounding, int $expected) {
    expect(app(AdjustRepairPrices::class)->adjust(7500, $percent, $rounding))->toBe($expected);
})->with([
    'up 10%, ending in 9' => [10, AdjustRepairPrices::ROUND_NINE, 8900],
    'up 10%, whole pounds' => [10, AdjustRepairPrices::ROUND_POUND, 8300],
    'up 10%, pennies' => [10, AdjustRepairPrices::ROUND_PENNY, 8250],
    'down 10%, ending in 9' => [-10, AdjustRepairPrices::ROUND_NINE, 6900],
    '0% still rounds £75 up to £79' => [0, AdjustRepairPrices::ROUND_NINE, 7900],
]);

it('applies a percentage change to a set of prices', function () {
    $changed = app(AdjustRepairPrices::class)->handle(RepairPrice::query()->get(), 5, AdjustRepairPrices::ROUND_POUND);

    expect($changed)->toBe(2)
        ->and($this->screenPrice->fresh()->price_pence)->toBe(18800)
        ->and($this->batteryPrice->fresh()->price_pence)->toBe(7900);
});
