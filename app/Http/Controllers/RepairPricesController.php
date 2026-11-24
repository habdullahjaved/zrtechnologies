<?php

namespace App\Http\Controllers;

use App\Enums\SystemPage;
use App\Models\Page;
use App\Services\Content\ServiceDirectory;
use App\Services\Pricing\PricingService;
use App\Services\Seo\SchemaGraph;
use App\Services\Seo\SeoResolver;
use Illuminate\Contracts\View\View;

/**
 * /repair-prices: the interactive price checker plus a full, crawlable price list.
 */
class RepairPricesController extends Controller
{
    public function __invoke(PricingService $pricing, SeoResolver $seo, SchemaGraph $schema, ServiceDirectory $directory): View
    {
        $page = Page::system(SystemPage::Prices);
        $breadcrumbs = [['label' => 'Repair prices']];
        $fromPrices = $pricing->fromPrices();

        return view('pages.repair-prices', [
            'seo' => $seo->forPage($page, $breadcrumbs)->withSchema([
                $schema->serviceNode('iPhone Repair', (string) $page->intro, route('prices'), $fromPrices),
            ]),
            'page' => $page,
            'breadcrumbs' => $breadcrumbs,
            'repairs' => $pricing->repairsWithFromPrices(),
            'models' => $pricing->modelsWithPrices(),
            'priceList' => $pricing->priceList(),
            'directory' => $directory,
        ]);
    }
}
