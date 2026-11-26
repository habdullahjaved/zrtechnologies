<?php

namespace App\Services\Seo;

use App\Enums\SystemPage;
use App\Models\Author;
use App\Models\DeviceModel;
use App\Models\ModelRepairPage;
use App\Models\Page;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\ServiceArea;
use App\Models\Tag;
use App\Models\Video;
use App\Services\Content\ServiceDirectory;
use App\Settings\SiteSettings;
use App\Support\PublicFile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Route;

/**
 * XML sitemap entries, split by type. Only pages search engines may index are
 * listed: no drafts, nothing marked noindex, no location or model × repair
 * page that has not passed the unique-content checks, and no tag pages
 * unless they are switched on. Built fresh on each request, so they always
 * reflect the current content.
 *
 * @phpstan-type SitemapImage array{loc: string, title?: string|null}
 * @phpstan-type SitemapVideo array{thumbnail: string, title: string, description: string, content_loc?: string|null, player_loc?: string|null, duration?: int|null, publication_date?: string|null}
 * @phpstan-type SitemapEntry array{loc: string, lastmod?: string|null, images?: list<SitemapImage>, video?: SitemapVideo}
 */
class SitemapBuilder
{
    public const array TYPES = ['pages', 'services', 'models', 'locations', 'blog', 'videos'];

    /**
     * System pages with their own templates, listed in the pages sitemap.
     *
     * @var list<SystemPage>
     */
    private const array TEMPLATE_PAGES = [
        SystemPage::Home, SystemPage::IphoneRepair, SystemPage::HowItWorks,
        SystemPage::Areas, SystemPage::Booking, SystemPage::Faqs, SystemPage::Reviews,
        SystemPage::Contact, SystemPage::Business, SystemPage::Blog, SystemPage::Videos,
    ];

    public function __construct(
        private readonly ServiceDirectory $directory,
        private readonly SiteSettings $site,
    ) {}

    /**
     * @return list<SitemapEntry>
     */
    public function entries(string $type): array
    {
        return match ($type) {
            'pages' => $this->pages(),
            'services' => $this->services(),
            'models' => $this->models(),
            'locations' => $this->locations(),
            'blog' => $this->blog(),
            'videos' => $this->videos(),
            default => [],
        };
    }

    /**
     * @return list<SitemapEntry>
     */
    private function pages(): array
    {
        $rows = Page::query()->with('seoMeta')->get();
        $entries = [];

        foreach (self::TEMPLATE_PAGES as $key) {
            $row = $rows->first(fn (Page $page): bool => $page->key === $key);

            if ($row !== null && (! $row->show_in_sitemap || $row->seoMeta?->noindex || ! $row->isLive())) {
                continue;
            }

            $entries[] = ['loc' => route($key->routeName()), 'lastmod' => $row?->updated_at?->toAtomString()];
        }

        foreach ($rows as $page) {
            $isContentPage = $page->key === null || $page->key->isContentPage();

            if (! $isContentPage || ! $page->isLive() || ! $page->show_in_sitemap || $page->seoMeta?->noindex) {
                continue;
            }

            if ($page->key !== null && ! Route::has($page->key->routeName())) {
                continue;
            }

            $entries[] = ['loc' => $page->url(), 'lastmod' => $page->updated_at?->toAtomString()];
        }

        return $entries;
    }

    /**
     * @return list<SitemapEntry>
     */
    private function services(): array
    {
        $services = $this->directory->services()->load('seoMeta');
        $entries = [];

        foreach ($services as $service) {
            if ($service->seoMeta?->noindex) {
                continue;
            }

            $entry = ['loc' => $service->url(), 'lastmod' => $service->updated_at?->toAtomString()];

            if (($image = PublicFile::url($service->og_image)) !== null) {
                $entry['images'] = [['loc' => $image, 'title' => 'iPhone '.$service->name]];
            }

            $entries[] = $entry;
        }

        return $entries;
    }

    /**
     * @return list<SitemapEntry>
     */
    private function models(): array
    {
        $entries = [];

        $models = DeviceModel::query()->listed()
            ->whereHas('prices', fn (Builder $prices) => $prices->available())
            ->with('seoMeta')
            ->get();

        foreach ($models as $model) {
            if (! $model->seoMeta?->noindex) {
                $entries[] = ['loc' => $model->url(), 'lastmod' => $model->updated_at?->toAtomString()];
            }
        }

        $repairPages = ModelRepairPage::query()->where('is_enabled', true)
            ->whereHas('deviceModel', fn (Builder $query) => $query->where('is_active', true))
            ->whereHas('repairType', fn (Builder $query) => $query->where('is_active', true))
            ->with(['deviceModel', 'repairType'])
            ->get();

        foreach ($repairPages as $page) {
            if ($page->passesContentChecks()) {
                $entries[] = [
                    'loc' => route('repairs.model-repair', [$page->deviceModel->slug, $page->repairType->slug]),
                    'lastmod' => $page->updated_at?->toAtomString(),
                ];
            }
        }

        return $entries;
    }

    /**
     * @return list<SitemapEntry>
     */
    private function locations(): array
    {
        $entries = [];

        foreach (ServiceArea::query()->listed()->with('seoMeta')->get() as $area) {
            if ($area->passesContentChecks() && ! $area->seoMeta?->noindex) {
                $entries[] = ['loc' => $area->url(), 'lastmod' => $area->updated_at?->toAtomString()];
            }
        }

        return $entries;
    }

    /**
     * @return list<SitemapEntry>
     */
    private function blog(): array
    {
        $entries = [];

        $categories = PostCategory::query()->ordered()->with('seoMeta')
            ->whereHas('posts', fn (Builder $posts) => $posts->live())
            ->get();

        foreach ($categories as $category) {
            if (! $category->seoMeta?->noindex) {
                $entries[] = ['loc' => $category->url()];
            }
        }

        foreach (Post::query()->live()->latestFirst()->with('seoMeta')->get() as $post) {
            if ($post->seoMeta?->noindex) {
                continue;
            }

            $entry = ['loc' => $post->url(), 'lastmod' => $post->lastUpdatedAt()?->toAtomString()];

            if (($image = $post->featuredImageUrl()) !== null) {
                $entry['images'] = [['loc' => $image, 'title' => $post->featured_image_alt ?? $post->title]];
            }

            $entries[] = $entry;
        }

        $authors = Author::query()->where('is_active', true)->whereHas('posts', fn (Builder $posts) => $posts->live())->get();

        foreach ($authors as $author) {
            $entries[] = ['loc' => $author->url()];
        }

        if ($this->site->index_tag_pages) {
            foreach (Tag::query()->whereHas('posts', fn (Builder $posts) => $posts->live())->get() as $tag) {
                $entries[] = ['loc' => $tag->url()];
            }
        }

        return $entries;
    }

    /**
     * @return list<SitemapEntry>
     */
    private function videos(): array
    {
        $entries = [];

        foreach (Video::query()->playable()->with('seoMeta')->orderBy('sort')->get() as $video) {
            if ($video->seoMeta?->noindex) {
                continue;
            }

            $entries[] = [
                'loc' => route('videos.show', $video->slug),
                'lastmod' => $video->updated_at?->toAtomString(),
                'video' => [
                    'thumbnail' => $video->posterUrl() ?? asset('images/og/og-videos.jpg'),
                    'title' => $video->title,
                    'description' => $video->description ?? $video->title,
                    'content_loc' => $video->fileUrl(),
                    'player_loc' => $video->embedUrl(),
                    'duration' => $video->duration_seconds,
                    'publication_date' => $video->published_at?->toAtomString(),
                ],
            ];
        }

        return $entries;
    }
}
