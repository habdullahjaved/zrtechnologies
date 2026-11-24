<?php

namespace App\Http\Controllers;

use App\Enums\SystemPage;
use App\Models\Page;
use App\Services\Seo\SeoResolver;
use App\Settings\BusinessSettings;
use App\Settings\OpeningHoursSettings;
use Illuminate\Contracts\View\View;

/**
 * /contact: message, callback and quick-quote forms plus our contact details.
 */
class ContactController extends Controller
{
    public function __invoke(BusinessSettings $business, OpeningHoursSettings $hours, SeoResolver $seo): View
    {
        $page = Page::system(SystemPage::Contact);
        $breadcrumbs = [['label' => 'Contact']];

        return view('pages.contact', [
            'seo' => $seo->forPage($page, $breadcrumbs, pageType: 'ContactPage'),
            'page' => $page,
            'breadcrumbs' => $breadcrumbs,
            'business' => $business,
            'hours' => $hours,
        ]);
    }
}
