<?php

use App\Models\DeviceModel;
use App\Models\PartQuality;
use App\Models\RepairPrice;
use App\Models\RepairType;
use App\Services\Pricing\PricingService;
use Illuminate\Database\UniqueConstraintViolationException;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    $this->pricing = app(PricingService::class);
});

it('charges the sale price only when it is lower than the price', function () {
    $onSale = RepairPrice::factory()->make(['price_pence' => 8900, 'sale_price_pence' => 7900]);
    $higherSale = RepairPrice::factory()->make(['price_pence' => 8900, 'sale_price_pence' => 9900]);

    expect($onSale->isOnSale())->toBeTrue()
        ->and($onSale->effectivePricePence())->toBe(7900)
        ->and($onSale->formattedPrice())->toBe('£79')
        ->and($higherSale->isOnSale())->toBeFalse()
        ->and($higherSale->effectivePricePence())->toBe(8900);
});

it('describes warranties in months where it can', function (int $days, ?string $label) {
    expect(RepairPrice::factory()->make(['warranty_days' => $days])->warrantyLabel())->toBe($label);
})->with([
    [365, '12 months'],
    [730, '24 months'],
    [180, '6 months'],
    [90, '3 months'],
    [30, '1 month'],
    [45, '45 days'],
    [0, null],
]);

it('works out the lowest visible price per repair, including sale prices', function () {
    $screen = RepairType::factory()->create();
    RepairPrice::factory()->for($screen)->create(['price_pence' => 9900]);
    RepairPrice::factory()->for($screen)->create(['price_pence' => 12900, 'sale_price_pence' => 6900]);
    RepairPrice::factory()->for($screen)->inactive()->create(['price_pence' => 1000]);
    RepairPrice::factory()->for($screen)->for(DeviceModel::factory()->inactive())->create(['price_pence' => 2000]);

    expect($this->pricing->fromPrices())->toBe([$screen->id => 6900])
        ->and($this->pricing->fromPrice($screen))->toBe(6900);
});

it('hides prices whose repair, model or part quality is switched off', function () {
    $model = DeviceModel::factory()->create();
    $offered = RepairType::factory()->create(['sort' => 1]);
    $notOffered = RepairType::factory()->inactive()->create();
    $retiredQuality = PartQuality::factory()->inactive()->create();

    RepairPrice::factory()->for($model)->for($offered)->create();
    RepairPrice::factory()->for($model)->for($offered)->for($retiredQuality)->create();
    RepairPrice::factory()->for($model)->for($notOffered)->create();

    $prices = $this->pricing->pricesForModel($model);

    expect($prices->keys()->all())->toBe([$offered->id])
        ->and($prices->get($offered->id))->toHaveCount(1);
});

it('groups a model’s prices by repair in display order, then by part quality', function () {
    $model = DeviceModel::factory()->create();
    $battery = RepairType::factory()->create(['sort' => 20]);
    $screen = RepairType::factory()->create(['sort' => 10]);
    $premium = PartQuality::factory()->create(['sort' => 20]);
    $standard = PartQuality::factory()->create(['sort' => 10]);

    RepairPrice::factory()->for($model)->for($battery)->create();
    RepairPrice::factory()->for($model)->for($screen)->for($premium)->create();
    RepairPrice::factory()->for($model)->for($screen)->for($standard)->create();

    $prices = $this->pricing->pricesForModel($model);

    expect($prices->keys()->all())->toBe([$screen->id, $battery->id])
        ->and($prices->get($screen->id)->map(fn (RepairPrice $price) => $price->part_quality_id)->all())->toBe([$standard->id, $premium->id]);
});

it('lists only active models that have visible prices, in display order', function () {
    $newer = DeviceModel::factory()->create(['sort' => 10]);
    $older = DeviceModel::factory()->create(['sort' => 20]);
    DeviceModel::factory()->create(['sort' => 5]);
    $hidden = DeviceModel::factory()->inactive()->create(['sort' => 1]);

    RepairPrice::factory()->for($older)->create();
    RepairPrice::factory()->for($newer)->create();
    RepairPrice::factory()->for($hidden)->create();

    expect($this->pricing->modelsWithPrices()->pluck('id')->all())->toBe([$newer->id, $older->id]);
});

it('allows only one price per model, repair and part quality, even without a quality', function () {
    $price = RepairPrice::factory()->create();

    expect(fn () => RepairPrice::factory()->create([
        'device_model_id' => $price->device_model_id,
        'repair_type_id' => $price->repair_type_id,
    ]))->toThrow(UniqueConstraintViolationException::class);
});

it('records price changes in the audit log', function () {
    $price = RepairPrice::factory()->create(['price_pence' => 5900]);

    $price->update(['price_pence' => 6900]);

    $activity = Activity::query()->where('log_name', 'prices')->latest('id')->first();

    expect($activity->attribute_changes['attributes']['price_pence'])->toBe(6900)
        ->and($activity->attribute_changes['old']['price_pence'])->toBe(5900);
});
