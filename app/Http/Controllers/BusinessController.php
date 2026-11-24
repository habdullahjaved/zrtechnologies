<?php

namespace App\Http\Controllers;

use App\Enums\SystemPage;
use App\Models\Page;
use App\Services\Seo\SeoResolver;
use App\Support\Content\BlockRenderer;
use Illuminate\Contracts\View\View;

/**
 * /business: repairs for companies, schools and other organisations.
 */
class BusinessController extends Controller
{
    public function __invoke(SeoResolver $seo, BlockRenderer $renderer): View
    {
        $page = Page::system(SystemPage::Business);
        $breadcrumbs = [['label' => 'Business repairs']];

        return view('pages.business', [
            'seo' => $seo->forPage($page, $breadcrumbs),
            'page' => $page,
            'content' => $renderer->render($page->blocks),
            'breadcrumbs' => $breadcrumbs,
        ]);
    }
}
