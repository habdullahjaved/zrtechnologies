<?php

namespace App\Http\Controllers;

use App\Support\Seo\SeoData;
use Illuminate\Contracts\View\View;

/**
 * /business: repairs for companies, schools and other organisations.
 */
class BusinessController extends Controller
{
    public function __invoke(): View
    {
        return view('pages.business', [
            'seo' => new SeoData(
                title: 'Business iPhone Repairs',
                description: 'iPhone repairs for businesses, schools and organisations across East Lancashire, collected from your site. Tell us what you need and we will put together a quote.',
                image: 'images/og/og-business.jpg',
                imageAlt: 'Business iPhone repairs from ZR Technologies',
            ),
        ]);
    }
}
