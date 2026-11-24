<?php

namespace App\Services\Seo;

use App\Models\Faq;
use App\Models\ServiceArea;
use App\Settings\BusinessSettings;
use App\Settings\OpeningHoursSettings;
use App\Settings\SiteSettings;
use App\Settings\SocialSettings;
use App\Support\Money;
use App\Support\Phone;
use App\Support\Seo\SeoData;

/**
 * The JSON-LD @graph for a page, built from settings and the database.
 *
 * Every page carries the business (LocalBusiness, which is also the
 * Organization), the WebSite, the WebPage and its BreadcrumbList. Pages add
 * their own nodes (Service + offers, FAQPage, BlogPosting, VideoObject...).
 *
 * Self-serving review stars (AggregateRating on our own business) are left out
 * on purpose: Google does not show them for a business's own reviews.
 */
class SchemaGraph
{
    /** @var list<array{'@type': string, name: string}>|null */
    private ?array $areaServed = null;

    public function __construct(
        private readonly BusinessSettings $business,
        private readonly SiteSettings $site,
        private readonly SocialSettings $social,
        private readonly OpeningHoursSettings $hours,
    ) {}

    public function businessId(): string
    {
        return url('/').'#business';
    }

    public function websiteId(): string
    {
        return url('/').'#website';
    }

    /**
     * @return array<string, mixed>
     */
    public function forPage(SeoData $seo, string $title, string $description, string $canonical, string $image): array
    {
        $graph = [
            $this->businessNode(),
            $this->websiteNode(),
            $this->webPageNode($seo, $title, $description, $canonical, $image),
        ];

        if ($seo->breadcrumbs !== []) {
            $graph[] = $this->breadcrumbNode($seo->breadcrumbs, $canonical);
        }

        return [
            '@context' => 'https://schema.org',
            '@graph' => [...$graph, ...$seo->schema],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function businessNode(): array
    {
        $node = [
            '@type' => 'LocalBusiness',
            '@id' => $this->businessId(),
            'name' => $this->business->trading_name,
            'legalName' => $this->business->legal_name,
            'slogan' => $this->business->tagline,
            'description' => $this->business->short_description,
            'url' => url('/'),
            'logo' => [
                '@type' => 'ImageObject',
                'url' => asset('brand/zr-logo-full-colour-600w.png'),
                'width' => 600,
            ],
            'image' => asset($this->site->default_og_image),
            'telephone' => Phone::toE164($this->business->phone),
            'email' => $this->business->email,
            'priceRange' => $this->business->price_range,
            'currenciesAccepted' => 'GBP',
            'address' => $this->address(),
            'areaServed' => $this->areaServed(),
            'identifier' => [
                '@type' => 'PropertyValue',
                'propertyID' => 'Companies House company number',
                'value' => $this->business->company_number,
            ],
            'founder' => ['@type' => 'Person', 'name' => $this->business->director_name],
            'openingHoursSpecification' => $this->openingHours(),
        ];

        if ($this->business->founded_year !== null) {
            $node['foundingDate'] = (string) $this->business->founded_year;
        }

        if (filled($this->business->vat_number)) {
            $node['vatID'] = $this->business->vat_number;
        }

        if (filled($this->social->google_business_profile)) {
            $node['hasMap'] = $this->social->google_business_profile;
        }

        if (($sameAs = $this->social->sameAs()) !== []) {
            $node['sameAs'] = $sameAs;
        }

        return $node;
    }

    /**
     * @return array<string, mixed>
     */
    public function websiteNode(): array
    {
        return [
            '@type' => 'WebSite',
            '@id' => $this->websiteId(),
            'url' => url('/'),
            'name' => $this->site->site_name,
            'inLanguage' => 'en-GB',
            'publisher' => ['@id' => $this->businessId()],
        ];
    }

    /**
     * A Service with its offers from the live price list.
     *
     * @param  iterable<int>  $pricesPence  Prices customers can see, in pence.
     * @return array<string, mixed>
     */
    public function serviceNode(string $name, string $description, string $url, iterable $pricesPence, ?string $image = null): array
    {
        $node = [
            '@type' => 'Service',
            '@id' => $url.'#service',
            'name' => $name,
            'description' => $description,
            'serviceType' => $name,
            'url' => $url,
            'provider' => ['@id' => $this->businessId()],
            'areaServed' => $this->areaServed(),
        ];

        if ($image !== null) {
            $node['image'] = $image;
        }

        $amounts = collect($pricesPence);

        if ($amounts->isNotEmpty()) {
            $node['offers'] = [
                '@type' => 'AggregateOffer',
                'priceCurrency' => 'GBP',
                'lowPrice' => Money::toPounds((int) $amounts->min()),
                'highPrice' => Money::toPounds((int) $amounts->max()),
                'offerCount' => $amounts->count(),
                'availability' => 'https://schema.org/InStock',
            ];
        }

        return $node;
    }

    /**
     * Only for FAQs that are visible on the page.
     *
     * @param  iterable<Faq>  $faqs
     * @return array<string, mixed>|null
     */
    public function faqNode(iterable $faqs, string $canonical): ?array
    {
        $questions = [];

        foreach ($faqs as $faq) {
            $questions[] = [
                '@type' => 'Question',
                'name' => $faq->question,
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => implode("\n\n", $faq->answerParagraphs())],
            ];
        }

        if ($questions === []) {
            return null;
        }

        return [
            '@type' => 'FAQPage',
            '@id' => $canonical.'#faq',
            'mainEntity' => $questions,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function webPageNode(SeoData $seo, string $title, string $description, string $canonical, string $image): array
    {
        $node = [
            '@type' => $seo->pageType,
            '@id' => $canonical.'#webpage',
            'url' => $canonical,
            'name' => $title,
            'description' => $description,
            'inLanguage' => 'en-GB',
            'isPartOf' => ['@id' => $this->websiteId()],
            'about' => ['@id' => $this->businessId()],
            'primaryImageOfPage' => ['@type' => 'ImageObject', 'url' => $image],
        ];

        if ($seo->breadcrumbs !== []) {
            $node['breadcrumb'] = ['@id' => $canonical.'#breadcrumb'];
        }

        if ($seo->publishedTime !== null) {
            $node['datePublished'] = $seo->publishedTime;
        }

        if ($seo->modifiedTime !== null) {
            $node['dateModified'] = $seo->modifiedTime;
        }

        return $node;
    }

    /**
     * @param  list<array{label: string, url?: string|null}>  $items
     * @return array<string, mixed>
     */
    private function breadcrumbNode(array $items, string $canonical): array
    {
        $elements = [[
            '@type' => 'ListItem',
            'position' => 1,
            'name' => 'Home',
            'item' => url('/'),
        ]];

        foreach ($items as $index => $item) {
            $isLast = $index === count($items) - 1;

            $elements[] = [
                '@type' => 'ListItem',
                'position' => $index + 2,
                'name' => $item['label'],
                'item' => $isLast ? $canonical : ($item['url'] ?? $canonical),
            ];
        }

        return [
            '@type' => 'BreadcrumbList',
            '@id' => $canonical.'#breadcrumb',
            'itemListElement' => $elements,
        ];
    }

    /**
     * The towns we collect from.
     *
     * @return list<array{'@type': string, name: string}>
     */
    private function areaServed(): array
    {
        return $this->areaServed ??= ServiceArea::query()->listed()->pluck('town')->unique()->values()
            ->map(fn (string $town): array => ['@type' => 'City', 'name' => $town])
            ->all();
    }

    /**
     * A service-area business shows only its town unless the owner chooses to
     * show the street address (Business settings).
     *
     * @return array<string, string>
     */
    private function address(): array
    {
        $address = [
            '@type' => 'PostalAddress',
            'addressLocality' => $this->business->registered_office_town,
            'addressRegion' => $this->business->registered_office_county ?? 'Lancashire',
            'addressCountry' => 'GB',
        ];

        if ($this->business->show_street_address) {
            $address['streetAddress'] = trim($this->business->registered_office_line1.' '.$this->business->registered_office_line2);
            $address['postalCode'] = $this->business->registered_office_postcode;
        }

        return $address;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function openingHours(): array
    {
        $specifications = [];

        foreach (OpeningHoursSettings::DAYS as $day => $label) {
            $hours = $this->hours->days[$day] ?? null;

            if ($hours === null || ! $hours['is_open'] || ! $hours['opens'] || ! $hours['closes']) {
                continue;
            }

            $specifications[] = [
                '@type' => 'OpeningHoursSpecification',
                'dayOfWeek' => 'https://schema.org/'.$label,
                'opens' => $hours['opens'],
                'closes' => $hours['closes'],
            ];
        }

        return $specifications;
    }
}
