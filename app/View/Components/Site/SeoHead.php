<?php

namespace App\View\Components\Site;

use App\Services\Seo\SchemaGraph;
use App\Settings\SiteSettings;
use App\Support\PublicFile;
use App\Support\Seo\Indexing;
use App\Support\Seo\SeoData;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\View\Component;

/**
 * Renders the page <title>, meta description, canonical, robots, Open Graph,
 * Twitter tags and the JSON-LD graph, merging the page's SeoData with
 * site-wide defaults.
 */
class SeoHead extends Component
{
    public string $title;

    public string $description;

    public string $canonical;

    public string $robots;

    public string $image;

    public string $imageAlt;

    public string $jsonLd;

    public function __construct(
        public SeoData $seo,
        public SiteSettings $site,
        Indexing $indexing,
        SchemaGraph $schema,
    ) {
        $this->title = $this->buildTitle();
        $this->description = Str::limit($seo->description ?? $site->default_meta_description, 160, '…', preserveWords: true);
        $this->canonical = $seo->canonical ?? url()->current();
        $this->robots = $this->buildRobots($indexing->isAllowed());
        $this->image = PublicFile::url($seo->image ?? $site->default_og_image) ?? asset($site->default_og_image);
        $this->imageAlt = $seo->imageAlt ?? $this->title;

        $this->jsonLd = (string) json_encode(
            $schema->forPage($seo, $this->title, $this->description, $this->canonical, $this->image),
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT,
        );
    }

    public function render(): View
    {
        return view('components.site.seo-head');
    }

    private function buildTitle(): string
    {
        $title = $this->seo->title ?? $this->site->default_meta_title;

        if (! $this->seo->appendSiteName || $this->seo->title === null || str_contains($title, $this->site->site_name)) {
            return $title;
        }

        $full = "{$title} {$this->site->title_separator} {$this->site->site_name}";

        // Google cuts long titles: past 70 characters keep the page's own words rather than the brand.
        return mb_strlen($full) > 70 ? $title : $full;
    }

    /**
     * Outside production (or with indexing switched off) nothing is indexed.
     * Thin or private pages are "noindex, follow" so links on them still count.
     */
    private function buildRobots(bool $indexingAllowed): string
    {
        if (! $indexingAllowed) {
            return 'noindex, nofollow';
        }

        $follow = $this->seo->nofollow ? 'nofollow' : 'follow';

        return $this->seo->noindex
            ? "noindex, {$follow}"
            : "index, {$follow}, max-image-preview:large, max-snippet:-1";
    }
}
