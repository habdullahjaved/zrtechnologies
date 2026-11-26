<?php

namespace App\View\Components\Site;

use App\Settings\BusinessSettings;
use App\Settings\OpeningHoursSettings;
use App\Settings\SiteSettings;
use App\Support\BookingCallToAction;
use App\Support\Navigation\SiteNavigation;
use App\Support\Phone;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * Sticky site header: logo, primary navigation, phone number and booking button,
 * with a slide-out drawer on mobile.
 */
class Header extends Component
{
    public string $phoneDisplay;

    public string $phoneHref;

    public string $whatsAppUrl;

    public string $bookingUrl;

    public bool $bookingOpensWhatsApp;

    /** @var list<array{label: string, url: string, active: bool, external: bool}> */
    public array $links;

    /** @var array{url: string, active: bool, services: list<array{label: string, url: string, icon: string|null}>, models: list<array{label: string, url: string}>}|null */
    public ?array $megaMenu;

    public ?string $announcement;

    public string $tradingName;

    public bool $openAllWeek;

    public function __construct(
        BusinessSettings $business,
        SiteSettings $site,
        SiteNavigation $navigation,
        BookingCallToAction $booking,
        OpeningHoursSettings $hours,
    ) {
        $this->openAllWeek = $hours->isOpenAllWeek();
        $this->tradingName = $business->trading_name;
        $this->phoneDisplay = Phone::display($business->phone);
        $this->phoneHref = Phone::telHref($business->phone);
        $this->whatsAppUrl = Phone::whatsAppUrl($business->whatsapp);
        $this->bookingUrl = $booking->url();
        $this->bookingOpensWhatsApp = $booking->opensWhatsApp();
        $this->links = $navigation->primary();
        $this->megaMenu = $navigation->megaMenu();
        $this->announcement = $site->announcement;
    }

    public function render(): View
    {
        return view('components.site.header');
    }
}
