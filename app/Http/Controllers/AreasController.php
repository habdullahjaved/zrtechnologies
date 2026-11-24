<?php

namespace App\Http\Controllers;

use App\Enums\SystemPage;
use App\Models\Page;
use App\Models\ServiceArea;
use App\Services\Seo\SeoResolver;
use Illuminate\Contracts\View\View;

/**
 * /areas-we-cover: postcode checker, coverage map and the towns we collect from.
 */
class AreasController extends Controller
{
    public function __invoke(SeoResolver $seo): View
    {
        $page = Page::system(SystemPage::Areas);
        $areas = ServiceArea::query()->listed()->get();
        $towns = $areas->pluck('town')->unique()->values();
        $breadcrumbs = [['label' => 'Areas we cover']];

        $townList = match ($towns->count()) {
            0 => 'East Lancashire',
            1 => $towns->first(),
            default => $towns->slice(0, -1)->implode(', ').' and '.$towns->last(),
        };

        return view('pages.areas', [
            'seo' => $seo->forPage($page, $breadcrumbs, pageType: 'CollectionPage'),
            'page' => $page,
            'breadcrumbs' => $breadcrumbs,
            'areas' => $areas,
            'townList' => $townList,
        ]);
    }
}
