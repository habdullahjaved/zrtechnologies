<?php

use App\Enums\StaffRole;
use App\Filament\Pages\Settings\ManageBookingSettings;
use App\Filament\Resources\AvailabilityOverrides\AvailabilityOverrideResource;
use App\Filament\Resources\AvailabilityOverrides\Pages\ManageAvailabilityOverrides;
use App\Filament\Resources\AvailabilityWindows\AvailabilityWindowResource;
use App\Filament\Resources\BlackoutDates\BlackoutDateResource;
use App\Filament\Resources\DeviceModels\DeviceModelResource;
use App\Filament\Resources\DeviceModels\Pages\CreateDeviceModel;
use App\Filament\Resources\PartQualities\PartQualityResource;
use App\Filament\Resources\RepairPrices\Pages\ManageRepairPrices;
use App\Filament\Resources\RepairPrices\RepairPriceResource;
use App\Filament\Resources\RepairTypes\RepairTypeResource;
use App\Filament\Resources\ServiceAreas\Pages\CreateServiceArea;
use App\Filament\Resources\ServiceAreas\ServiceAreaResource;
use App\Filament\Resources\ServiceCategories\ServiceCategoryResource;
use App\Models\AvailabilityWindow;
use App\Models\DeviceBrand;
use App\Models\DeviceModel;
use App\Models\RepairPrice;
use App\Models\RepairType;
use App\Models\ServiceArea;
use App\Models\User;
use App\Settings\BookingSettings;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Actions\Testing\TestAction;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    config(['zr.admin.require_mfa' => false]);
});

it('gives each role the right access to the catalogue and coverage screens', function (StaffRole $role, bool $prices, bool $models, bool $areas, bool $timetable, bool $bookingRules) {
    $this->actingAs(User::factory()->withRole($role)->create());

    $this->get(RepairPriceResource::getUrl('index'))->assertStatus($prices ? 200 : 403);
    $this->get(DeviceModelResource::getUrl('index'))->assertStatus($models ? 200 : 403);
    $this->get(RepairTypeResource::getUrl('index'))->assertStatus($models ? 200 : 403);
    $this->get(ServiceAreaResource::getUrl('index'))->assertStatus($areas ? 200 : 403);
    $this->get(AvailabilityWindowResource::getUrl('index'))->assertStatus($timetable ? 200 : 403);
    $this->get(BlackoutDateResource::getUrl('index'))->assertStatus($timetable ? 200 : 403);
    $this->get(ManageBookingSettings::getUrl())->assertStatus($bookingRules ? 200 : 403);
})->with([
    'super admin' => [StaffRole::SuperAdmin, true, true, true, true, true],
    'manager' => [StaffRole::Manager, true, true, true, true, true],
    'technician' => [StaffRole::Technician, true, true, false, false, false],
    'content editor' => [StaffRole::ContentEditor, true, true, true, false, false],
    'sales/support' => [StaffRole::SalesSupport, true, true, true, true, false],
]);

it('lets only managers change prices', function () {
    $price = RepairPrice::factory()->create();

    expect(User::factory()->withRole(StaffRole::Manager)->create()->can('update', $price))->toBeTrue()
        ->and(User::factory()->withRole(StaffRole::ContentEditor)->create()->can('update', $price))->toBeFalse()
        ->and(User::factory()->withRole(StaffRole::Technician)->create()->can('update', $price))->toBeFalse()
        ->and(User::factory()->withRole(StaffRole::SalesSupport)->create()->can('delete', $price))->toBeFalse();
});

it('loads every catalogue and coverage screen for a super admin', function () {
    $this->actingAs(User::factory()->superAdmin()->create());
    RepairPrice::factory()->create();

    foreach ([PartQualityResource::class, ServiceCategoryResource::class, AvailabilityOverrideResource::class] as $resource) {
        $this->get($resource::getUrl('index'))->assertOk();
    }

    $this->get(DeviceModelResource::getUrl('create'))->assertOk();
    $this->get(ServiceAreaResource::getUrl('create'))->assertOk();
    $this->get(RepairTypeResource::getUrl('edit', ['record' => RepairType::query()->first()]))->assertOk();
});

it('edits prices inline in pounds', function () {
    $this->actingAs(User::factory()->withRole(StaffRole::Manager)->create());
    $price = RepairPrice::factory()->create(['price_pence' => 5900]);

    livewire(ManageRepairPrices::class)
        ->call('updateTableColumnState', 'price_pounds', (string) $price->getKey(), '64.50')
        ->call('updateTableColumnState', 'sale_price_pounds', (string) $price->getKey(), '59');

    expect($price->fresh())
        ->price_pence->toBe(6450)
        ->sale_price_pence->toBe(5900);
});

it('confirms draft prices in bulk', function () {
    $this->actingAs(User::factory()->withRole(StaffRole::Manager)->create());
    $drafts = RepairPrice::factory()->count(3)->draft()->create();

    livewire(ManageRepairPrices::class)
        ->assertSee('3 prices still marked DRAFT')
        ->selectTableRecords($drafts->take(2)->pluck('id')->all())
        ->callAction(TestAction::make('confirm')->table()->bulk());

    expect(RepairPrice::query()->where('is_draft', true)->count())->toBe(1);
});

it('changes selected prices by a percentage', function () {
    $this->actingAs(User::factory()->withRole(StaffRole::Manager)->create());
    $price = RepairPrice::factory()->create(['price_pence' => 10000]);

    livewire(ManageRepairPrices::class)
        ->selectTableRecords([$price->getKey()])
        ->callAction(TestAction::make('adjust')->table()->bulk(), ['percent' => 10, 'rounding' => 'pound'])
        ->assertHasNoFormErrors();

    expect($price->fresh()->price_pence)->toBe(11000);
});

it('adds a price through the form, refusing a duplicate', function () {
    $this->actingAs(User::factory()->withRole(StaffRole::Manager)->create());
    $existing = RepairPrice::factory()->create();

    livewire(ManageRepairPrices::class)
        ->callAction('create', [
            'device_model_id' => $existing->device_model_id,
            'repair_type_id' => $existing->repair_type_id,
            'part_quality_id' => null,
            'price_pence' => '49.99',
            'warranty_days' => 90,
        ])
        ->assertHasFormErrors(['repair_type_id']);

    $otherRepair = RepairType::factory()->create();

    livewire(ManageRepairPrices::class)
        ->callAction('create', [
            'device_model_id' => $existing->device_model_id,
            'repair_type_id' => $otherRepair->id,
            'price_pence' => '49.99',
            'sale_price_pence' => '',
            'warranty_days' => 180,
        ])
        ->assertHasNoFormErrors();

    expect(RepairPrice::query()->whereBelongsTo($otherRepair)->sole())
        ->price_pence->toBe(4999)
        ->sale_price_pence->toBeNull()
        ->is_draft->toBeTrue();
});

it('creates a device model, keeping slugs unique across models and repairs', function () {
    $this->actingAs(User::factory()->superAdmin()->create());
    DeviceBrand::factory()->create(['slug' => DeviceBrand::APPLE]);
    RepairType::factory()->create(['slug' => 'battery-replacement']);

    livewire(CreateDeviceModel::class)
        ->fillForm(['name' => 'Battery Replacement', 'slug' => 'battery-replacement'])
        ->call('create')
        ->assertHasFormErrors(['slug']);

    livewire(CreateDeviceModel::class)
        ->fillForm(['name' => 'iPhone 19 Pro'])
        ->assertSchemaStateSet(['slug' => 'iphone-19-pro'])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(DeviceModel::query()->where('slug', 'iphone-19-pro')->sole()->brand->slug)->toBe(DeviceBrand::APPLE);
});

it('stores service area districts in upper case and rejects invalid ones', function () {
    $this->actingAs(User::factory()->superAdmin()->create());

    livewire(CreateServiceArea::class)
        ->fillForm(['name' => 'Rossendale', 'town' => 'Rawtenstall', 'postcode_districts' => ['BB4', 'not a district']])
        ->call('create')
        ->assertHasFormErrors(['postcode_districts.1']);

    livewire(CreateServiceArea::class)
        ->fillForm(['name' => 'Rossendale', 'town' => 'Rawtenstall', 'postcode_districts' => ['bb4', 'BB4'], 'collection_fee_pence' => '7.50'])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(ServiceArea::query()->where('slug', 'rossendale')->sole())
        ->postcode_districts->toBe(['BB4'])
        ->collection_fee_pence->toBe(750);
});

it('only allows capacity changes for windows on that weekday', function () {
    $this->actingAs(User::factory()->superAdmin()->create());
    $monday = AvailabilityWindow::factory()->create(['weekday' => 1]);
    AvailabilityWindow::factory()->create(['weekday' => 2]);

    livewire(ManageAvailabilityOverrides::class)
        ->mountAction('create')
        ->fillForm(['date' => '2026-11-30'])
        ->assertFormFieldExists('availability_window_id', fn ($field): bool => array_keys($field->getOptions()) === [$monday->id]);
});

it('saves booking rules', function () {
    $this->actingAs(User::factory()->withRole(StaffRole::Manager)->create());

    livewire(ManageBookingSettings::class)
        ->fillForm(['lead_time_hours' => 4, 'same_day_cutoff' => '11:30', 'max_days_ahead' => 14, 'post_in_enabled' => true])
        ->call('save')
        ->assertHasNoFormErrors();

    $settings = freshSettings(BookingSettings::class);

    expect($settings->lead_time_hours)->toBe(4)
        ->and($settings->same_day_cutoff)->toBe('11:30')
        ->and($settings->max_days_ahead)->toBe(14)
        ->and($settings->post_in_enabled)->toBeTrue();
});
