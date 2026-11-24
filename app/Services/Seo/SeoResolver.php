<?php

namespace App\Services\Seo;

use App\Models\Page;
use App\Models\SeoMeta;
use App\Support\Content\SystemPageDefaults;
use App\Support\Seo\SeoData;
use Illuminate\Support\Str;

/**
 * Builds a page's SEO metadata in one place: the record's own overrides first,
 * then the page's generated defaults (title patterns), then site defaults
 * (applied when the head is rendered).
 */
class SeoResolver
{
    /**
     * @param  list<array{label: string, url?: string|null}>  $breadcrumbs
     * @param  list<array<string, mixed>>  $schema
     */
    public function make(
        ?SeoMeta $meta,
        ?string $title,
        ?string $description = null,
        ?string $image = null,
        ?string $imageAlt = null,
        bool $noindex = false,
        ?string $canonical = null,
        array $breadcrumbs = [],
        array $schema = [],
        string $type = 'website',
        string $pageType = 'WebPage',
        bool $appendSiteName = true,
        ?string $publishedTime = null,
        ?string $modifiedTime = null,
    ): SeoData {
        // Optional nodes (e.g. FAQPage when there are no FAQs) arrive as null.
        $schema = array_values(array_filter($schema));
        $override = $meta?->schema_override;

        if (is_array($override) && $override !== []) {
            $schema = [...$schema, ...(array_is_list($override) ? $override : [$override])];
        }

        return new SeoData(
            title: filled($meta?->title) ? $meta->title : $title,
            description: filled($meta?->description) ? $meta->description : $description,
            canonical: filled($meta?->canonical_url) ? $meta->canonical_url : $canonical,
            noindex: $noindex || (bool) $meta?->noindex,
            image: filled($meta?->og_image) ? $meta->og_image : $image,
            imageAlt: filled($meta?->og_image_alt) ? $meta->og_image_alt : $imageAlt,
            type: $type,
            appendSiteName: $appendSiteName,
            nofollow: (bool) $meta?->nofollow,
            breadcrumbs: $breadcrumbs,
            schema: $schema,
            pageType: $pageType,
            publishedTime: $publishedTime,
            modifiedTime: $modifiedTime,
        );
    }

    /**
     * SEO for a page row (system or custom page).
     *
     * @param  list<array{label: string, url?: string|null}>  $breadcrumbs
     * @param  list<array<string, mixed>>  $schema
     */
    public function forPage(Page $page, array $breadcrumbs = [], array $schema = [], string $pageType = 'WebPage', ?string $fallbackTitle = null): SeoData
    {
        $page->loadMissing('seoMeta');

        return $this->make(
            meta: $page->seoMeta,
            title: $fallbackTitle ?? $page->title,
            description: $page->intro ? Str::limit(strip_tags($page->intro), 160, '…', preserveWords: true) : null,
            image: $page->key !== null ? SystemPageDefaults::ogImage($page->key) : null,
            noindex: ! $page->isLive(),
            breadcrumbs: $breadcrumbs,
            schema: $schema,
            pageType: $pageType,
            modifiedTime: $page->updated_at?->toIso8601String(),
        );
    }

    /**
     * Fill a title pattern, e.g. "{repair} for {model} | From {price}".
     * Segments whose placeholder is empty are dropped, so a missing price
     * never leaves "From " behind.
     *
     * @param  array<string, string|null>  $values
     */
    public function pattern(string $pattern, array $values): string
    {
        $segments = preg_split('/\s+\|\s+/', $pattern) ?: [$pattern];
        $kept = [];

        foreach ($segments as $segment) {
            $missing = false;

            $filled = (string) preg_replace_callback('/\{(\w+)\}/', function (array $matches) use ($values, &$missing): string {
                $value = $values[$matches[1]] ?? null;

                if (blank($value)) {
                    $missing = true;

                    return '';
                }

                return $value;
            }, $segment);

            if (! $missing) {
                $kept[] = trim($filled);
            }
        }

        return implode(' | ', array_filter($kept));
    }
}
