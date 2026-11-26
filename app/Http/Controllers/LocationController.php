<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use App\Models\Review;
use App\Models\Service;
use App\Models\ServiceArea;
use App\Services\Content\ServiceDirectory;
use App\Services\Seo\SchemaGraph;
use App\Services\Seo\SeoResolver;
use App\Settings\SiteSettings;
use App\Support\Content\BlockRenderer;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;

/**
 * /iphone-repair-{town}: a location page for a town we collect from.
 *
 * Until its local content passes the unique-content checks, the page is
 * "noindex, follow" with its canonical pointing at /areas-we-cover, so it can
 * never be treated as a doorway page.
 */
class LocationController extends Controller
{
    public function __invoke(
        string $slug,
        ServiceDirectory $directory,
        SeoResolver $seo,
        SchemaGraph $schema,
        BlockRenderer $renderer,
        SiteSettings $site,
    ): View {
        $area = ServiceArea::query()->listed()->where('slug', $slug)
            ->with(['seoMeta', 'faqs' => fn ($faqs) => $faqs->where('is_active', true)])
            ->firstOrFail();

        $canonical = $area->url();
        $passes = $area->passesContentChecks();
        $breadcrumbs = [
            ['label' => 'Areas we cover', 'url' => route('areas.index')],
            ['label' => "iPhone repair in {$area->town}"],
        ];

        /** @var Collection<int, Faq> $faqs */
        $faqs = $area->faqs->isNotEmpty() ? $area->faqs : Faq::query()->listed()->where('topic', 'booking')->limit(4)->get();

        $localReviews = Review::query()->displayable()->where('service_area_id', $area->getKey())->limit(3)->get();
        $reviews = $localReviews->isNotEmpty() ? $localReviews : Review::query()->displayable()->limit(3)->get();

        $seoData = $seo->make(
            meta: null,
            title: $seo->pattern($site->title_pattern_location, ['town' => $area->town]),
            description: "iPhone repair in {$area->town} with collection and delivery. We collect from your door in ".implode(', ', $area->postcode_districts).', repair your iPhone and bring it back.',
            image: 'images/og/og-areas-we-cover.jpg',
            imageAlt: "iPhone repair in {$area->town}",
            noindex: ! $passes,
            canonical: $passes ? $canonical : route('areas.index'),
            breadcrumbs: $breadcrumbs,
            schema: [
                $schema->serviceNode("iPhone Repair in {$area->town}", "iPhone repair with collection and delivery in {$area->town}.", $canonical, []),
                $schema->faqNode($faqs, $canonical),
            ],
        );

        return view('pages.locations.show', [
            'seo' => $seoData,
            'area' => $area,
            'content' => $renderer->render($area->blocks),
            'breadcrumbs' => $breadcrumbs,
            'services' => $directory->services()->map(fn (Service $service): array => ['service' => $service]),
            'reviews' => $reviews,
            'faqs' => $faqs,
            'otherAreas' => ServiceArea::query()->listed()->whereKeyNot($area->getKey())->get(),
        ]);
    }
}
