<?php

use App\Enums\PublishStatus;
use App\Enums\SystemPage;
use App\Models\DeviceModel;
use App\Models\Page;
use App\Models\RepairType;
use App\Models\Service;
use App\Models\ServiceArea;
use Database\Seeders\CatalogueSeeder;
use Database\Seeders\ContentSeeder;
use Database\Seeders\CoverageSeeder;

/**
 * Every public page with the seeded content: one H1 and a unique title,
 * description and canonical.
 */
it('renders every public page with one H1 and unique titles, descriptions and canonicals', function () {
    $this->seed([CatalogueSeeder::class, CoverageSeeder::class, ContentSeeder::class]);

    $urls = [
        route('home'), route('repairs.index'), route('prices'), route('areas.index'), route('how-it-works'),
        route('faqs'), route('reviews'), route('contact'), route('business'), route('videos.index'),
        route('blog.index'), route('booking.create'), route('track-repair'),
        route('about'), route('warranty'),
        route('legal.privacy'), route('legal.cookies'), route('legal.terms'), route('legal.repair-terms'), route('legal.accessibility'),
        ...Service::query()->listed()->get()->map->url()->all(),
        ...ServiceArea::query()->listed()->get()->map->url()->all(),
        DeviceModel::query()->where('slug', 'iphone-15-pro')->firstOrFail()->url(),
        route('blog.show', 'battery-health'),
    ];

    $titles = $descriptions = $canonicals = [];

    foreach ($urls as $url) {
        $html = $this->get($url)->assertOk()->getContent();

        expect(substr_count($html, '<h1'))->toBe(1, "One H1 on {$url}");

        preg_match('~<title>(.*?)</title>~', $html, $title);
        preg_match('~<meta name="description" content="([^"]*)"~', $html, $description);
        preg_match('~<link rel="canonical" href="([^"]*)"~', $html, $canonical);

        $titles[$url] = $title[1];
        $descriptions[$url] = $description[1];
        $canonicals[$url] = $canonical[1];
    }

    expect(array_unique($titles))->toHaveCount(count($urls))
        ->and(array_unique($descriptions))->toHaveCount(count($urls))
        ->and(array_unique($canonicals))->toHaveCount(count($urls));
});

it('serves the repair pages: service first, then model, otherwise not found', function () {
    $this->seed([CatalogueSeeder::class, CoverageSeeder::class, ContentSeeder::class]);

    $this->get('/iphone-repair/screen-replacement')
        ->assertOk()
        ->assertSee('iPhone Screen Replacement in Blackburn')
        ->assertSee('iPhone 15 Pro Max')
        ->assertSee('<title>iPhone Screen Replacement in Blackburn | From £49 | ZR Technologies</title>', false);

    $this->get('/iphone-repair/camera-repair')
        ->assertOk()
        ->assertSee('Rear camera')
        ->assertSee('Camera lens');

    $this->get('/iphone-repair/iphone-15-pro')
        ->assertOk()
        ->assertSee('iPhone 15 Pro Repair in Blackburn')
        ->assertSee('OLED')
        ->assertDontSee('From £20');

    $this->get('/iphone-repair/not-a-real-page')->assertNotFound();
});

it('hides a repair page while none of its repairs are offered', function () {
    $this->seed([CatalogueSeeder::class, CoverageSeeder::class, ContentSeeder::class]);

    RepairType::query()->where('slug', 'face-id-repair')->update(['is_active' => false]);

    $this->get('/iphone-repair/face-id-repair')->assertNotFound();
    $this->get(route('repairs.index'))->assertDontSee('/iphone-repair/face-id-repair', false);
});

it('serves custom pages at their slug only once they are published', function () {
    $page = Page::factory()->create(['title' => 'Careers', 'slug' => 'careers']);
    $draft = Page::factory()->draft()->create(['slug' => 'secret-plans']);

    $this->get('/careers')->assertOk()->assertSee('Careers');
    $this->get('/secret-plans')->assertNotFound();

    $page->update(['status' => PublishStatus::Scheduled, 'published_at' => now()->addDay()]);
    $this->get('/careers')->assertNotFound();

    expect($draft->fresh())->not->toBeNull();
});

it('never deletes a system page', function () {
    $page = Page::factory()->system(SystemPage::Privacy)->create();

    $page->delete();

    expect($page->fresh()?->trashed())->toBeFalse();
});

it('shows the legal pages with their last-updated date', function () {
    $this->seed(ContentSeeder::class);

    $this->get(route('legal.privacy'))
        ->assertOk()
        ->assertSee('ZR TECHNOLOGIES LTD')
        ->assertSee('Information Commissioner')
        ->assertSee('Last updated');

    $this->get(route('legal.repair-terms'))->assertOk()->assertSee('14 days');

    expect(Page::query()->where('needs_legal_review', true)->count())->toBe(5);
});
