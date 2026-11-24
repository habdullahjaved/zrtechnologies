<?php

namespace App\Http\Controllers;

use App\Models\ServiceArea;
use App\Support\Seo\SeoData;
use Illuminate\Contracts\View\View;

/**
 * /areas-we-cover: postcode checker and the towns we collect from.
 */
class AreasController extends Controller
{
    public function __invoke(): View
    {
        $areas = ServiceArea::query()->listed()->get();
        $towns = $areas->pluck('town')->unique()->values();

        $townList = match ($towns->count()) {
            0 => 'East Lancashire',
            1 => $towns->first(),
            default => $towns->slice(0, -1)->implode(', ').' and '.$towns->last(),
        };

        return view('pages.areas', [
            'seo' => new SeoData(
                title: 'Areas We Cover for iPhone Collection & Repair',
                description: "We collect and return iPhones across {$townList}. Check your postcode to see if we collect from you.",
                image: 'images/og/og-areas-we-cover.jpg',
                imageAlt: 'Areas covered by ZR Technologies iPhone collection and repair',
            ),
            'areas' => $areas,
            'townList' => $townList,
        ]);
    }
}
