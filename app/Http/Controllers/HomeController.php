<?php

namespace App\Http\Controllers;

use App\Enums\SystemPage;
use App\Models\Faq;
use App\Models\Page;
use App\Models\Post;
use App\Models\RepairType;
use App\Models\Review;
use App\Models\Service;
use App\Models\ServiceArea;
use App\Models\ServiceCategory;
use App\Models\Video;
use App\Services\Content\ServiceDirectory;
use App\Services\Pricing\PricingService;
use App\Services\Seo\SchemaGraph;
use App\Services\Seo\SeoResolver;
use App\Settings\BusinessSettings;
use App\Settings\SiteSettings;
use App\Support\BookingCallToAction;
use App\Support\Phone;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;

/**
 * The home page, in the order set out in the brief: hero, booking wizard, how
 * it works, popular repairs, why us, videos, areas, reviews, latest guides,
 * FAQs, the call-to-action band and the "coming soon" teaser.
 */
class HomeController extends Controller
{
    public function __invoke(
        BusinessSettings $business,
        SiteSettings $site,
        BookingCallToAction $booking,
        ServiceDirectory $directory,
        SeoResolver $seo,
        SchemaGraph $schema,
        PricingService $pricing,
    ): View {
        $page = Page::system(SystemPage::Home);
        $page->loadMissing(['faqs' => fn ($faqs) => $faqs->where('is_active', true)]);

        /** @var Collection<int, Faq> $faqs */
        $faqs = $page->faqs->isNotEmpty() ? $page->faqs : Faq::query()->listed()->where('show_on_faq_page', true)->limit(6)->get();

        // Service pages that cover a popular repair; before any service pages exist, the popular repairs themselves.
        $popular = $directory->services()
            ->filter(fn (Service $service): bool => $service->repairTypes->contains(fn (RepairType $repair): bool => $repair->is_popular))
            ->map(fn (Service $service): array => [
                'name' => $service->name,
                'summary' => $service->summary,
                'icon' => $service->icon,
                'url' => $service->url(),
            ])
            ->values()
            ->all();

        if ($popular === []) {
            $popular = collect($pricing->repairsWithFromPrices())
                ->filter(fn (array $item): bool => $item['repair']->is_popular)
                ->map(fn (array $item): array => [
                    'name' => $item['repair']->name,
                    'summary' => $item['repair']->summary,
                    'icon' => $item['repair']->icon,
                    'url' => route('booking.create', ['repair' => $item['repair']->slug]),
                ])
                ->values()
                ->all();
        }

        return view('pages.home', [
            'seo' => $seo->make(
                meta: $page->seoMeta,
                title: null,
                image: 'images/og/og-home.jpg',
                imageAlt: 'ZR Technologies: iPhone repair with collection and delivery in Blackburn',
                canonical: route('home'),
            )->withSchema([$schema->faqNode($faqs, route('home'))]),
            'page' => $page,
            'bookingUrl' => $booking->url(),
            'bookingOpensWhatsApp' => $booking->opensWhatsApp(),
            'phoneHref' => Phone::telHref($business->phone),
            'phoneDisplay' => Phone::display($business->phone),
            'popularServices' => $popular,
            'videos' => Video::query()->listed()->where('show_on_home', true)->limit(6)->get(),
            'areas' => ServiceArea::query()->listed()->get(),
            'reviews' => Review::query()->displayable()->limit(3)->get(),
            'posts' => Post::query()->live()->latestFirst()->with('category')->limit(3)->get(),
            'faqs' => $faqs,
            'comingSoon' => $site->show_coming_soon
                ? ServiceCategory::query()->where('is_active', false)->orderBy('sort')->get()
                : new Collection,
        ]);
    }
}
