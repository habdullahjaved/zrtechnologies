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
    ) {}
}
