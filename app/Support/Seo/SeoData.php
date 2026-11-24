<?php

namespace App\Support\Seo;

/**
 * SEO metadata for one page. Anything left null falls back to the defaults in
 * Website & SEO settings when the page head is rendered.
 */
final readonly class SeoData
{
    /**
     * @param  string|null  $title  Page title without the site name suffix.
     * @param  string|null  $canonical  Absolute canonical URL; defaults to the current URL without its query string.
     * @param  string|null  $image  Share image path relative to /public, or an absolute URL.
     * @param  list<array{label: string, url?: string|null}>  $breadcrumbs  Trail after "Home"; the last item is the current page.
     * @param  list<array<string, mixed>>  $schema  Extra JSON-LD nodes for this page (Service, FAQPage, BlogPosting...).
     * @param  string  $pageType  schema.org type of the WebPage node, e.g. AboutPage, ContactPage, CollectionPage.
     */
    public function __construct(
        public ?string $title = null,
        public ?string $description = null,
        public ?string $canonical = null,
        public bool $noindex = false,
        public ?string $image = null,
        public ?string $imageAlt = null,
        public string $type = 'website',
        public bool $appendSiteName = true,
        public bool $nofollow = false,
        public array $breadcrumbs = [],
        public array $schema = [],
        public string $pageType = 'WebPage',
        public ?string $publishedTime = null,
        public ?string $modifiedTime = null,
    ) {}

    /**
     * A copy with extra structured-data nodes appended.
     *
     * @param  list<array<string, mixed>>  $nodes
     */
    public function withSchema(array $nodes): self
    {
        return new self(
            title: $this->title,
            description: $this->description,
            canonical: $this->canonical,
            noindex: $this->noindex,
            image: $this->image,
            imageAlt: $this->imageAlt,
            type: $this->type,
            appendSiteName: $this->appendSiteName,
            nofollow: $this->nofollow,
            breadcrumbs: $this->breadcrumbs,
            schema: [...$this->schema, ...array_values(array_filter($nodes))],
            pageType: $this->pageType,
            publishedTime: $this->publishedTime,
            modifiedTime: $this->modifiedTime,
        );
    }
}
