<?php

namespace App\Http\Controllers;

use App\Services\Pricing\PricingService;
use App\Support\Seo\SeoData;
use Illuminate\Contracts\View\View;

/**
 * /repair-prices: the interactive price checker plus a full, crawlable price list.
 */
class RepairPricesController extends Controller
{
    public function __invoke(PricingService $pricing): View
    {
        $priceList = $pricing->priceList();

        return view('pages.repair-prices', [
            'seo' => new SeoData(
                title: 'iPhone Repair Prices in Blackburn',
                description: 'Clear, fixed iPhone repair prices for every model, from screens and batteries to charging ports and cameras. Collection and return across Blackburn.',
                image: 'images/og/og-repair-prices.jpg',
                imageAlt: 'ZR Technologies iPhone repair prices',
            ),
            'repairs' => $pricing->repairsWithFromPrices(),
            'models' => $pricing->modelsWithPrices(),
            'priceList' => $priceList,
        ]);
    }
}
