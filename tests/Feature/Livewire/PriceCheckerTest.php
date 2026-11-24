<?php

use App\Livewire\PriceChecker;
use App\Models\DeviceModel;
use App\Models\PartQuality;
use App\Models\RepairPrice;
use App\Models\RepairType;
use Livewire\Livewire;

beforeEach(function () {
    $this->model = DeviceModel::factory()->popular()->create(['name' => 'iPhone 15', 'slug' => 'iphone-15']);
    $this->screen = RepairType::factory()->create(['name' => 'Screen Replacement', 'sort' => 10]);
    $this->water = RepairType::factory()->requiresDiagnosis()->create(['name' => 'Water Damage Repair', 'sort' => 20]);
    $this->premium = PartQuality::factory()->create(['name' => 'Premium']);

    RepairPrice::factory()->for($this->model)->for($this->screen)->for($this->premium)->create(['price_pence' => 17900, 'warranty_days' => 365]);
    RepairPrice::factory()->for($this->model)->for($this->water)->create(['price_pence' => 5900, 'warranty_days' => 90]);
});

it('lists models with prices and suggests popular ones', function () {
    Livewire::test(PriceChecker::class)
        ->assertSee('Choose your model…')
        ->assertSeeHtml('<option value="iphone-15">iPhone 15</option>')
        ->assertSee('Popular models')
        ->assertDontSee('Screen Replacement');
});

it('shows every repair price for the chosen model', function () {
    Livewire::test(PriceChecker::class)
        ->set('model', 'iphone-15')
        ->assertSee('iPhone 15 repair prices')
        ->assertSee('Screen Replacement')
        ->assertSee('Premium')
        ->assertSee('£179')
        ->assertSee('12 months warranty')
        ->assertSee('Water Damage Repair')
        ->assertSeeHtml('<span class="text-sm">from</span>')
        ->assertSee('£59');
});

it('links each price to the booking form, prefilled with the model and repair', function () {
    $url = route('booking.create', ['model' => 'iphone-15', 'repair' => $this->screen->slug, 'quality' => $this->premium->slug]);

    Livewire::test(PriceChecker::class)
        ->call('selectModel', 'iphone-15')
        ->assertSeeHtml(e($url));
});

it('opens on a model from the link, and ignores unknown models', function () {
    Livewire::test(PriceChecker::class, ['model' => 'iphone-15'])->assertSet('model', 'iphone-15');
    Livewire::test(PriceChecker::class, ['model' => 'iphone-404'])->assertSet('model', '');
    Livewire::test(PriceChecker::class)->call('selectModel', 'nokia-3310')->assertSet('model', '');
});

it('never shows hidden prices', function () {
    RepairPrice::query()->whereBelongsTo($this->water)->update(['is_active' => false]);

    Livewire::test(PriceChecker::class)
        ->set('model', 'iphone-15')
        ->assertSee('Screen Replacement')
        ->assertDontSee('Water Damage Repair');
});
