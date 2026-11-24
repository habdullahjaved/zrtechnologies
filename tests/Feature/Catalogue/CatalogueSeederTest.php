<?php

use App\Models\AvailabilityWindow;
use App\Models\BlackoutDate;
use App\Models\DeviceModel;
use App\Models\RepairPrice;
use App\Models\RepairType;
use App\Models\ServiceArea;
use App\Models\ServiceCategory;
use Database\Seeders\CatalogueSeeder;
use Database\Seeders\CoverageSeeder;

beforeEach(function () {
    $this->seed([CatalogueSeeder::class, CoverageSeeder::class]);
});

it('seeds the iPhone line-up from the iPhone 8 to the current models', function () {
    expect(DeviceModel::query()->count())->toBe(39)
        ->and(DeviceModel::query()->where('slug', 'iphone-8')->exists())->toBeTrue()
        ->and(DeviceModel::query()->where('slug', 'iphone-se-2nd-generation')->exists())->toBeTrue()
        ->and(DeviceModel::query()->where('slug', 'iphone-18-pro-max')->exists())->toBeTrue()
        ->and(DeviceModel::query()->orderBy('sort')->first()->release_year)->toBe(2026);
});

it('seeds twelve repair types under iPhone Repair, the only live service', function () {
    $iphoneRepair = ServiceCategory::query()->where('slug', ServiceCategory::IPHONE_REPAIR)->sole();

    expect(RepairType::query()->count())->toBe(12)
        ->and(RepairType::query()->where('service_category_id', '!=', $iphoneRepair->id)->exists())->toBeFalse()
        ->and(ServiceCategory::query()->active()->pluck('slug')->all())->toBe([ServiceCategory::IPHONE_REPAIR]);
});

it('seeds every price as a draft', function () {
    expect(RepairPrice::query()->count())->toBeGreaterThan(450)
        ->and(RepairPrice::query()->where('is_draft', false)->exists())->toBeFalse();
});

it('does not offer Face ID repair on Touch ID models', function () {
    $faceId = RepairType::query()->where('slug', 'face-id-repair')->sole();

    foreach (['iphone-8', 'iphone-8-plus', 'iphone-se-2nd-generation', 'iphone-se-3rd-generation'] as $slug) {
        $model = DeviceModel::query()->where('slug', $slug)->sole();
        expect(RepairPrice::query()->whereBelongsTo($model)->whereBelongsTo($faceId)->exists())->toBeFalse();
    }

    $iphoneX = DeviceModel::query()->where('slug', 'iphone-x')->sole();
    expect(RepairPrice::query()->whereBelongsTo($iphoneX)->whereBelongsTo($faceId)->exists())->toBeTrue();
});

it('offers screens in two part qualities and everything else in one', function () {
    $model = DeviceModel::query()->where('slug', 'iphone-15')->sole();

    $screenPrices = RepairPrice::query()->whereBelongsTo($model)
        ->whereHas('repairType', fn ($query) => $query->where('slug', 'screen-replacement'))
        ->get();

    expect($screenPrices)->toHaveCount(2)
        ->and($screenPrices->pluck('part_quality_id')->filter())->toHaveCount(2);
});

it('seeds draft service areas, with the optional towns switched off', function () {
    expect(ServiceArea::query()->count())->toBe(8)
        ->and(ServiceArea::query()->where('is_draft', false)->exists())->toBeFalse()
        ->and(ServiceArea::query()->listed()->pluck('slug')->all())
        ->toBe(['blackburn', 'darwen', 'accrington-oswaldtwistle', 'great-harwood', 'clitheroe']);
});

it('seeds a Monday-to-Saturday collection timetable and bank holidays', function () {
    expect(AvailabilityWindow::query()->where('weekday', 7)->exists())->toBeFalse()
        ->and(AvailabilityWindow::query()->where('weekday', 6)->count())->toBe(2)
        ->and(AvailabilityWindow::query()->where('weekday', 1)->count())->toBe(3)
        ->and(BlackoutDate::query()->whereDate('date', '2026-12-25')->exists())->toBeTrue();
});

it('never overwrites the owner’s edits when seeded again', function () {
    $price = RepairPrice::query()->first();
    $price->update(['price_pence' => 12345, 'is_draft' => false]);
    ServiceArea::query()->where('slug', 'preston')->update(['is_active' => true]);
    AvailabilityWindow::query()->first()->update(['capacity' => 9]);
    $counts = [RepairPrice::query()->count(), AvailabilityWindow::query()->count()];

    $this->seed([CatalogueSeeder::class, CoverageSeeder::class]);

    expect($price->fresh()->price_pence)->toBe(12345)
        ->and($price->fresh()->is_draft)->toBeFalse()
        ->and(ServiceArea::query()->where('slug', 'preston')->sole()->is_active)->toBeTrue()
        ->and(AvailabilityWindow::query()->first()->capacity)->toBe(9)
        ->and([RepairPrice::query()->count(), AvailabilityWindow::query()->count()])->toBe($counts);
});
