<?php

namespace App\Support;

use App\Settings\BusinessSettings;
use Illuminate\Support\Facades\Route;

/**
 * Where "Book a Repair" buttons point. Once the online booking wizard exists
 * (route "booking.create") they go there; until then they open WhatsApp with a
 * prefilled booking message, so the button always works.
 */
final class BookingCallToAction
{
    public const string ROUTE = 'booking.create';

    public function __construct(private BusinessSettings $business) {}

    public function url(): string
    {
        if (Route::has(self::ROUTE)) {
            return route(self::ROUTE);
        }

        return Phone::whatsAppUrl(
            $this->business->whatsapp,
            "Hi {$this->business->trading_name}, I'd like to book an iPhone repair.",
        );
    }

    public function opensWhatsApp(): bool
    {
        return ! Route::has(self::ROUTE);
    }
}
