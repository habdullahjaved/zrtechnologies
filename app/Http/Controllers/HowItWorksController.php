<?php

namespace App\Http\Controllers;

use App\Enums\SystemPage;
use App\Models\Faq;
use App\Models\Page;
use App\Services\Seo\SchemaGraph;
use App\Services\Seo\SeoResolver;
use App\Settings\BookingSettings;
use App\Support\Content\BlockRenderer;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;

/**
 * /how-it-works: collection → diagnosis → repair → return, with timings.
 */
class HowItWorksController extends Controller
{
    public function __invoke(SeoResolver $seo, SchemaGraph $schema, BlockRenderer $renderer, BookingSettings $booking): View
    {
        $page = Page::system(SystemPage::HowItWorks);
        $page->loadMissing(['faqs' => fn ($faqs) => $faqs->where('is_active', true)]);

        /** @var Collection<int, Faq> $faqs */
        $faqs = $page->faqs->isNotEmpty() ? $page->faqs : Faq::query()->listed()->where('topic', 'booking')->limit(6)->get();
        $breadcrumbs = [['label' => 'How it works']];

        return view('pages.how-it-works', [
            'seo' => $seo->forPage($page, $breadcrumbs)->withSchema([$schema->faqNode($faqs, route('how-it-works'))]),
            'page' => $page,
            'content' => $renderer->render($page->blocks),
            'breadcrumbs' => $breadcrumbs,
            'faqs' => $faqs,
            'leadTimeHours' => $booking->lead_time_hours,
            'paymentNote' => $booking->payment_note,
            'passcodeNotice' => $booking->passcode_notice,
        ]);
    }
}
