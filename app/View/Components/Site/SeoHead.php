<?php

namespace App\View\Components\Site;

use App\Settings\SiteSettings;
use App\Support\Seo\Indexing;
use App\Support\Seo\SeoData;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\View\Component;

/**
 * Renders the page <title>, meta description, canonical, robots, Open Graph
 * and Twitter tags, merging the page's SeoData with site-wide defaults.
 */
class SeoHead extends Component
{
    public string $title;

    public string $description;

    public string $canonical;

    public string $robots;

    public string $image;

    public string $imageAlt;

    public function __construct(
        public SeoData $seo,
        public SiteSettings $site,
        Indexing $indexing,
    ) {
        $this->title = $this->buildTitle();
        $this->description = Str::limit($seo->description ?? $site->default_meta_description, 160, '…', preserveWords: true);
        $this->canonical = $seo->canonical ?? url()->current();
        $this->robots = (! $seo->noindex && $indexing->isAllowed())
            ? 'index, follow, max-image-preview:large, max-snippet:-1'
            : 'noindex, nofollow';
        $this->image = $this->absoluteUrl($seo->image ?? $site->default_og_image);
        $this->imageAlt = $seo->imageAlt ?? $this->title;
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

        return "{$title} {$this->site->title_separator} {$this->site->site_name}";
    }

    private function absoluteUrl(string $path): string
    {
        return Str::startsWith($path, ['http://', 'https://']) ? $path : asset(ltrim($path, '/'));
    }
}
