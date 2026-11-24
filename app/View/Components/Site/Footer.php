<?php

namespace App\View\Components\Site;

use App\Settings\BusinessSettings;
use App\Settings\OpeningHoursSettings;
use App\Settings\SiteSettings;
use App\Settings\SocialSettings;
use App\Support\Navigation\SiteNavigation;
use App\Support\Phone;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * Site footer: NAP block, opening hours, links, company details (Companies Act
 * trading disclosures) and the trademark disclaimer.
 */
class Footer extends Component
{
    public string $phoneDisplay;

    public string $phoneHref;

    public string $whatsAppUrl;

    /** @var array<string, string> */
    public array $hours;

    /** @var array<string, array{label: string, url: string}> */
    public array $socialLinks;

    /** @var list<array{label: string, url: string, active: bool, external: bool}> */
    public array $companyLinks;

    /** @var list<array{label: string, url: string}> */
    public array $serviceLinks;

    /** @var list<array{label: string, url: string}> */
    public array $areaLinks;

    /** @var list<array{label: string, url: string, active: bool, external: bool}> */
    public array $legalLinks;

    public function __construct(
        public BusinessSettings $business,
        public SiteSettings $site,
        public OpeningHoursSettings $openingHours,
        SocialSettings $social,
        SiteNavigation $navigation,
    ) {
        $this->phoneDisplay = Phone::display($business->phone);
        $this->phoneHref = Phone::telHref($business->phone);
        $this->whatsAppUrl = Phone::whatsAppUrl($business->whatsapp);
        $this->hours = $openingHours->summary();
        $this->socialLinks = $social->filledNetworks();
        $this->companyLinks = $navigation->footerCompany();
        $this->legalLinks = $navigation->footerLegal();
        $this->serviceLinks = $navigation->footerServices();
        $this->areaLinks = $navigation->footerAreas();
    }

    public function render(): View
    {
        return view('components.site.footer');
    }
}
