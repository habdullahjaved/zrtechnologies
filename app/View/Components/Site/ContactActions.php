<?php

namespace App\View\Components\Site;

use App\Settings\BusinessSettings;
use App\Support\BookingCallToAction;
use App\Support\Phone;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * The three conversion actions (Call, WhatsApp, Book), rendered either as the
 * sticky mobile bar or as the gradient call-to-action band.
 */
class ContactActions extends Component
{
    public string $phoneDisplay;

    public string $phoneHref;

    public string $whatsAppUrl;

    public string $bookingUrl;

    public bool $bookingOpensWhatsApp;

    /**
     * @param  'mobile-bar'|'band'  $variant
     */
    public function __construct(
        BusinessSettings $business,
        BookingCallToAction $booking,
        public string $variant = 'band',
        public ?string $title = null,
        public ?string $text = null,
    ) {
        $this->phoneDisplay = Phone::display($business->phone);
        $this->phoneHref = Phone::telHref($business->phone);
        $this->whatsAppUrl = Phone::whatsAppUrl($business->whatsapp, "Hi {$business->trading_name}, I have a question about an iPhone repair.");
        $this->bookingUrl = $booking->url();
        $this->bookingOpensWhatsApp = $booking->opensWhatsApp();
    }

    public function render(): View
    {
        return view($this->variant === 'mobile-bar'
            ? 'components.site.mobile-cta-bar'
            : 'components.site.cta-band');
    }
}
