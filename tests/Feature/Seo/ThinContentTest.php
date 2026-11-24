<?php

use App\Models\DeviceModel;
use App\Models\ModelRepairPage;
use App\Models\RepairPrice;
use App\Models\RepairType;
use App\Models\ServiceArea;
use App\Services\Seo\ContentQuality;
use App\Settings\SiteSettings;

beforeEach(function () {
    // Robots rules only show "index" when indexing is allowed.
    config(['zr.indexable_environments' => ['testing']]);
    $site = app(SiteSettings::class);
    $site->allow_indexing = true;
    $site->save();
});

it('keeps a thin location page noindex, with its canonical on the areas page', function () {
    $area = ServiceArea::factory()->create(['town' => 'Darwen', 'slug' => 'darwen', 'intro' => 'We collect in Darwen.']);

    $this->get($area->url())
        ->assertOk()
        ->assertSee('<meta name="robots" content="noindex, follow">', false)
        ->assertSee('<link rel="canonical" href="'.route('areas.index').'">', false);
});

it('indexes a location page once its own content is long enough and unique', function () {
    $area = ServiceArea::factory()->create(['town' => 'Darwen', 'slug' => 'darwen', 'intro' => fake()->words(420, true)]);

    $this->get($area->url())
        ->assertSee('content="index, follow', false)
        ->assertSee('<link rel="canonical" href="'.$area->url().'">', false)
        ->assertSee('<title>iPhone Repair Darwen | Collection &amp; Delivery | ZR Technologies</title>', false);
});

it('flags a location page that copies another town', function () {
    $text = fake()->words(450, true);
    $first = ServiceArea::factory()->create(['intro' => $text]);
    $copy = ServiceArea::factory()->create(['intro' => $text.' With a different town name.']);

    expect((float) $copy->fresh()->similarity_score)->toBeGreaterThan(90)
        ->and($copy->fresh()->passesContentChecks())->toBeFalse()
        ->and($first->fresh()->passesContentChecks())->toBeFalse();
});

it('measures similarity by shared three-word phrases', function () {
    expect(ContentQuality::similarity('we collect your iphone from home', 'we collect your iphone from home'))->toBe(100.0)
        ->and(ContentQuality::similarity('we collect your iphone from home', 'cracked screens and tired batteries'))->toBe(0.0)
        ->and(ContentQuality::similarity('', 'anything'))->toBe(0.0);
});

it('serves model × repair pages only when switched on, noindex until they pass', function () {
    $model = DeviceModel::factory()->create(['slug' => 'iphone-15']);
    $repair = RepairType::factory()->create(['slug' => 'screen-replacement', 'name' => 'Screen Replacement']);
    RepairPrice::factory()->for($model)->for($repair)->create(['price_pence' => 12900]);
    $url = route('repairs.model-repair', ['iphone-15', 'screen-replacement']);

    $this->get($url)->assertNotFound();

    $page = ModelRepairPage::factory()->for($model)->for($repair)->create(['intro' => 'A short note.']);

    $this->get($url)
        ->assertOk()
        ->assertSee('Screen Replacement for '.$model->name)
        ->assertSee('£129')
        ->assertSee('<meta name="robots" content="noindex, follow">', false)
        ->assertSee('<link rel="canonical" href="'.$model->url().'">', false);

    $page->update(['intro' => fake()->words(320, true)]);

    $this->get($url)
        ->assertSee('content="index, follow', false)
        ->assertSee('<link rel="canonical" href="'.$url.'">', false);

    $page->update(['is_enabled' => false]);
    $this->get($url)->assertNotFound();
});
