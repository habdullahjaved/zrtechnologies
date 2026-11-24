<?php

use App\Models\Faq;
use App\Models\RepairPrice;
use App\Models\RepairType;
use App\Models\Service;
use App\Models\ServiceArea;
use App\Settings\BusinessSettings;
use App\Settings\SocialSettings;

/**
 * @return list<array<string, mixed>>
 */
function jsonLdGraph(string $html): array
{
    preg_match('~<script type="application/ld\+json">(.*?)</script>~s', $html, $matches);

    $json = json_decode($matches[1] ?? '', true, flags: JSON_THROW_ON_ERROR);

    expect($json['@context'])->toBe('https://schema.org');

    return $json['@graph'];
}

/**
 * @param  list<array<string, mixed>>  $graph
 * @return array<string, mixed>|null
 */
function graphNode(array $graph, string $type): ?array
{
    foreach ($graph as $node) {
        if ($node['@type'] === $type) {
            return $node;
        }
    }

    return null;
}

it('describes the business as a service-area LocalBusiness from settings', function () {
    ServiceArea::factory()->create(['town' => 'Darwen']);
    $social = app(SocialSettings::class);
    $social->facebook = 'https://www.facebook.com/zrtechnologies';
    $social->save();

    $graph = jsonLdGraph($this->get(route('home'))->getContent());
    $business = graphNode($graph, 'LocalBusiness');

    expect($business['legalName'])->toBe('ZR TECHNOLOGIES LTD')
        ->and($business['telephone'])->toBe('+447360127538')
        ->and($business['identifier']['value'])->toBe('17589309')
        ->and($business['areaServed'])->toContain(['@type' => 'City', 'name' => 'Darwen'])
        ->and($business['sameAs'])->toContain('https://www.facebook.com/zrtechnologies')
        ->and($business['address'])->not->toHaveKey('streetAddress')
        ->and($business)->not->toHaveKey('aggregateRating')
        ->and(graphNode($graph, 'WebSite'))->not->toBeNull();
});

it('shows the street address only when the owner chooses to', function () {
    $business = app(BusinessSettings::class);
    $business->show_street_address = true;
    $business->save();

    $node = graphNode(jsonLdGraph($this->get(route('home'))->getContent()), 'LocalBusiness');

    expect($node['address']['streetAddress'])->toBe('98 Moorgate Street')
        ->and($node['address']['postalCode'])->toBe('BB2 4NY');
});

it('adds a Service with real prices, breadcrumbs and visible FAQs to a repair page', function () {
    $repair = RepairType::factory()->create(['slug' => 'battery-replacement']);
    RepairPrice::factory()->for($repair)->create(['price_pence' => 4900]);
    RepairPrice::factory()->for($repair)->create(['price_pence' => 8900]);
    $service = Service::factory()->covering($repair)->create(['name' => 'Battery Replacement', 'slug' => 'battery-replacement']);
    $faq = Faq::factory()->create(['question' => 'How long does a battery last?']);
    $service->faqs()->attach($faq, ['sort' => 1]);

    $response = $this->get($service->url())->assertOk()->assertSee('How long does a battery last?');
    $graph = jsonLdGraph($response->getContent());

    $offers = graphNode($graph, 'Service')['offers'];
    $breadcrumbs = graphNode($graph, 'BreadcrumbList')['itemListElement'];

    expect($offers)->toMatchArray(['@type' => 'AggregateOffer', 'priceCurrency' => 'GBP', 'lowPrice' => '49.00', 'highPrice' => '89.00', 'offerCount' => 2])
        ->and(array_column($breadcrumbs, 'name'))->toBe(['Home', 'iPhone Repair', 'Battery Replacement'])
        ->and(graphNode($graph, 'FAQPage')['mainEntity'][0]['name'])->toBe('How long does a battery last?');
});

it('leaves FAQPage out when no FAQs are shown', function () {
    $repair = RepairType::factory()->create();
    RepairPrice::factory()->for($repair)->create();
    $service = Service::factory()->covering($repair)->create();

    expect(graphNode(jsonLdGraph($this->get($service->url())->getContent()), 'FAQPage'))->toBeNull();
});

it('adds schema overrides from the SEO fields', function () {
    $repair = RepairType::factory()->create();
    RepairPrice::factory()->for($repair)->create();
    $service = Service::factory()->covering($repair)->create();
    $service->seoMeta()->create(['schema_override' => ['@type' => 'Event', 'name' => 'Open day']]);

    expect(graphNode(jsonLdGraph($this->get($service->url())->getContent()), 'Event')['name'])->toBe('Open day');
});

it('escapes structured data so content cannot break out of the script tag', function () {
    $repair = RepairType::factory()->create();
    RepairPrice::factory()->for($repair)->create();
    $service = Service::factory()->covering($repair)->create(['summary' => 'Fixed </script><script>alert(1)</script>']);

    $html = $this->get($service->url())->getContent();

    expect($html)->not->toContain('</script><script>alert(1)');
    jsonLdGraph($html);
});
