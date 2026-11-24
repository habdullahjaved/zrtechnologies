<?php

namespace App\Http\Controllers;

use App\Settings\BusinessSettings;
use App\Support\BookingCallToAction;
use App\Support\Phone;
use App\Support\Seo\SeoData;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(BusinessSettings $business, BookingCallToAction $booking): View
    {
        return view('pages.home', [
            'seo' => new SeoData(
                image: 'images/og/og-home.jpg',
                imageAlt: 'ZR Technologies: iPhone repair with collection and delivery in Blackburn',
            ),
            'bookingUrl' => $booking->url(),
            'bookingOpensWhatsApp' => $booking->opensWhatsApp(),
            'phoneHref' => Phone::telHref($business->phone),
            'phoneDisplay' => Phone::display($business->phone),
        ]);
    }
}
