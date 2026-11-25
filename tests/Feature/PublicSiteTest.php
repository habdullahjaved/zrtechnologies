<?php

use App\Models\RepairPrice;
use App\Models\RepairType;
use App\Models\ServiceArea;
use App\Settings\BusinessSettings;
use App\Settings\SiteSettings;

it('renders the home page with one H1 and the business details from settings', function () {
    $response = $this->get(route('home'));

    $response->assertOk()
        ->assertSee('<html lang="en-GB"', false)
        ->assertSee('07360 127538')
        ->assertSee('tel:+447360127538', false)
        ->assertSee('https://wa.me/447360127538', false)
        ->assertSee('mrumar@zrtechnologiesltd.com')
        ->assertSee('company number 17589309')
        ->assertSee('98 Moorgate Street, Blackburn, Lancashire, BB2 4NY')
        ->assertSee('not affiliated with or endorsed by Apple Inc.');

    expect(substr_count($response->getContent(), '<h1'))->toBe(1);
});

it('reflects changes to business settings immediately', function () {
    $business = app(BusinessSettings::class);
    $business->phone = '+447400123459';
    $business->company_number = '00000001';
    $business->save();

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('07400 123459')
        ->assertSee('company number 00000001')
        ->assertDontSee('07360 127538');
});

it('shows VAT and ICO numbers in the footer only once they are filled', function () {
    $this->get(route('home'))->assertDontSee('VAT number')->assertDontSee('ICO registration');

    $business = app(BusinessSettings::class);
    $business->vat_number = 'GB123456789';
    $business->ico_registration_number = 'ZB123456';
    $business->save();

    $this->get(route('home'))->assertSee('VAT number GB123456789')->assertSee('ICO registration ZB123456');
});

it('renders the SEO head with canonical, Open Graph and the UK locale', function () {
    $this->get(route('home'))
        ->assertSee('<link rel="canonical" href="'.route('home').'">', false)
        ->assertSee('<meta property="og:locale" content="en_GB">', false)
        ->assertSee('og-home.jpg', false)
        ->assertSee('<title>iPhone Repair Blackburn | We Collect, Repair &amp; Return</title>', false);
});

it('is never indexable outside production', function () {
    $site = app(SiteSettings::class);
    $site->allow_indexing = true;
    $site->save();

    $this->get(route('home'))->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    $this->get('/robots.txt')->assertOk()->assertSeeText("User-agent: *\nDisallow: /");
});

it('is indexable in production only when switched on in settings', function () {
    app()->detectEnvironment(fn () => 'production');

    $site = app(SiteSettings::class);
    $site->allow_indexing = false;
    $site->save();

    $this->get(route('home'))->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    expect(explode("\n", $this->get('/robots.txt')->getContent()))->toContain('Disallow: /');

    $site->allow_indexing = true;
    $site->save();

    $this->get(route('home'))->assertSee('content="index, follow', false);

    expect(explode("\n", $this->get('/robots.txt')->getContent()))
        ->toContain('Disallow: /admin')
        ->not->toContain('Disallow: /');
});

it('sends baseline security headers', function () {
    $this->get(route('home'))
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
});

it('points Book a Repair at the online booking', function () {
    $this->get(route('home'))
        ->assertSee('href="'.route('booking.create').'"', false);
});

it('shows the announcement bar only when one is set', function () {
    $this->get(route('home'))->assertDontSee('Closed for the bank holiday');

    $site = app(SiteSettings::class);
    $site->announcement = 'Closed for the bank holiday on Monday';
    $site->save();

    $this->get(route('home'))->assertSee('Closed for the bank holiday on Monday');
});

it('shows a branded 404 page that is not indexed', function () {
    $this->get('/this-page-does-not-exist')
        ->assertNotFound()
        ->assertSee('Sorry, we can&#039;t find that page', false)
        ->assertSee('noindex, nofollow', false)
        ->assertSee('07360 127538');
});

it('serves the style guide only in local development', function () {
    expect(app('router')->has('styleguide'))->toBe(app()->isLocal());
});

it('renders the repair prices page with a crawlable price list', function () {
    $price = RepairPrice::factory()->create(['price_pence' => 8900]);

    $response = $this->get(route('prices'))
        ->assertOk()
        ->assertSee('<title>iPhone Repair Prices in Blackburn | ZR Technologies</title>', false)
        ->assertSee($price->deviceModel->name)
        ->assertSee('£89');

    expect(substr_count($response->getContent(), '<h1'))->toBe(1);
});

it('renders the areas page with the active towns and their districts', function () {
    ServiceArea::factory()->districts(['BB3'])->create(['name' => 'Darwen', 'town' => 'Darwen']);
    ServiceArea::factory()->districts(['PR1'])->inactive()->create(['name' => 'Preston', 'town' => 'Preston']);

    $response = $this->get(route('areas.index'))
        ->assertOk()
        ->assertSee('Darwen')
        ->assertSee('BB3')
        ->assertDontSee('Preston')
        ->assertSee('Check we collect from you');

    expect(substr_count($response->getContent(), '<h1'))->toBe(1);
});

it('links the new pages from the header and shows popular repairs on the home page', function () {
    $popular = RepairType::factory()->popular()->create(['name' => 'Battery Replacement']);
    RepairPrice::factory()->for($popular)->create(['price_pence' => 4900]);

    $this->get(route('home'))
        ->assertSee('href="'.route('prices').'"', false)
        ->assertSee('href="'.route('areas.index').'"', false)
        ->assertSeeInOrder(['The repairs we do most', 'Battery Replacement', 'from', '£49']);
});
