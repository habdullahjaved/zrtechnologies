<?php

namespace App\View\Components\Site;

use App\Settings\ConsentSettings;
use App\Settings\TrackingSettings;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * Runs first in <head>: sets Google Consent Mode v2 defaults to "denied",
 * applies a choice the visitor has already made (from the consent cookie)
 * and hands the tag IDs to the site script, which loads tags only as
 * consent allows.
 *
 * The consent check happens in the browser, so the same cached page works
 * for every visitor.
 */
class TrackingHead extends Component
{
    /** @var array<string, mixed> */
    public array $config;

    public string $policyVersion;

    public function __construct(TrackingSettings $tracking, ConsentSettings $consent)
    {
        $this->policyVersion = $consent->policy_version;

        $this->config = [
            'gtm' => $tracking->gtm_container_id ?: null,
            'ga4' => $tracking->ga4_measurement_id ?: null,
            'ads' => [
                'id' => $tracking->google_ads_conversion_id ?: null,
                'sendTo' => array_filter([
                    'book_repair' => $tracking->googleAdsSendTo('book_repair'),
                    'generate_lead' => $tracking->googleAdsSendTo('generate_lead'),
                    'click_to_call' => $tracking->googleAdsSendTo('click_to_call'),
                    'click_whatsapp' => $tracking->googleAdsSendTo('click_whatsapp'),
                ]),
            ],
            'enhancedConversions' => $tracking->enhanced_conversions,
            'meta' => $tracking->meta_pixel_id ?: null,
            'clarity' => $tracking->clarity_project_id ?: null,
            'tiktok' => $tracking->tiktok_pixel_id ?: null,
            'testMode' => $tracking->test_mode,
            'policyVersion' => $consent->policy_version,
            'consentUrl' => route('consent.store'),
            'eventUrl' => route('tracking.event'),
        ];
    }

    public function render(): View
    {
        return view('components.site.tracking-head');
    }
}
