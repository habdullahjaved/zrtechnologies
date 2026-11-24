<?php

namespace App\Http\Controllers;

use App\Enums\FaqTopic;
use App\Enums\SystemPage;
use App\Models\Faq;
use App\Models\Page;
use App\Services\Seo\SchemaGraph;
use App\Services\Seo\SeoResolver;
use Illuminate\Contracts\View\View;

/**
 * /faqs: every FAQ marked for the FAQ page, grouped by topic, with FAQPage
 * structured data for the questions shown.
 */
class FaqController extends Controller
{
    public function __invoke(SeoResolver $seo, SchemaGraph $schema): View
    {
        $page = Page::system(SystemPage::Faqs);
        $faqs = Faq::query()->listed()->where('show_on_faq_page', true)->get();
        $breadcrumbs = [['label' => 'FAQs']];

        $topics = [];

        foreach (FaqTopic::cases() as $topic) {
            $inTopic = $faqs->filter(fn (Faq $faq): bool => $faq->topic === $topic)->values();

            if ($inTopic->isNotEmpty()) {
                $topics[] = ['topic' => $topic, 'faqs' => $inTopic];
            }
        }

        return view('pages.faqs', [
            'seo' => $seo->forPage($page, $breadcrumbs)->withSchema([$schema->faqNode($faqs, route('faqs'))]),
            'page' => $page,
            'breadcrumbs' => $breadcrumbs,
            'topics' => $topics,
        ]);
    }
}
