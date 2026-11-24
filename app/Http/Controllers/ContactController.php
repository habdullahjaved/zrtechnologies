<?php

namespace App\Http\Controllers;

use App\Settings\BusinessSettings;
use App\Settings\OpeningHoursSettings;
use App\Support\Seo\SeoData;
use Illuminate\Contracts\View\View;

/**
 * /contact: message, callback and quick-quote forms plus our contact details.
 */
class ContactController extends Controller
{
    public function __invoke(BusinessSettings $business, OpeningHoursSettings $hours): View
    {
        return view('pages.contact', [
            'seo' => new SeoData(
                title: 'Contact Us',
                description: "Call, WhatsApp or message {$business->trading_name} about an iPhone repair. Ask for a quote or a callback and we will get back to you quickly.",
                image: 'images/og/og-contact.jpg',
                imageAlt: 'Contact ZR Technologies',
            ),
            'business' => $business,
            'hours' => $hours,
        ]);
    }
}
