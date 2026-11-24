<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Analytics and advertising IDs. Everything is optional: a platform is only
 * used once its ID is filled in, and only after the visitor consents.
 *
 * With a Google Tag Manager container set, the website pushes events to the
 * dataLayer and GTM fires the tags (docs/gtm-container.json). Without one,
 * the website loads Google's tag, the Meta Pixel and Clarity itself.
 */
class TrackingSettings extends Settings
{
    public ?string $gtm_container_id;

    public ?string $ga4_measurement_id;

    /** GA4 Measurement Protocol API secret, for server-side events. */
    public ?string $ga4_api_secret;

    /** e.g. AW-123456789 */
    public ?string $google_ads_conversion_id;

    public ?string $google_ads_booking_label;

    public ?string $google_ads_lead_label;

    public ?string $google_ads_call_label;

    public ?string $google_ads_whatsapp_label;

    /** Conversion action name used in the offline conversions CSV. */
    public string $google_ads_offline_conversion_name;

    /** Send hashed email and phone with Google Ads conversions (enhanced conversions). */
    public bool $enhanced_conversions;

    public ?string $meta_pixel_id;

    /** Meta Conversions API access token. */
    public ?string $meta_capi_token;

    /** From Meta Events Manager → Test events. Only used while test mode is on. */
    public ?string $meta_test_event_code;

    public ?string $clarity_project_id;

    public ?string $tiktok_pixel_id;

    /** Sends server events as tests (Meta test events, GA4 debug) and logs them as tests. */
    public bool $test_mode;

    /** Secret in the offline-conversions CSV link. */
    public string $export_token;

    public static function group(): string
    {
        return 'tracking';
    }

    /**
     * @return list<string>
     */
    public static function encrypted(): array
    {
        return ['ga4_api_secret', 'meta_capi_token', 'export_token'];
    }

    public function usesTagManager(): bool
    {
        return filled($this->gtm_container_id);
    }

    public function ga4ServerConfigured(): bool
    {
        return filled($this->ga4_measurement_id) && filled($this->ga4_api_secret);
    }

    public function metaServerConfigured(): bool
    {
        return filled($this->meta_pixel_id) && filled($this->meta_capi_token);
    }

    /**
     * Google Ads "send_to" for a website event, e.g. AW-123/AbC, or null when not set up.
     */
    public function googleAdsSendTo(string $event): ?string
    {
        $label = match ($event) {
            'book_repair' => $this->google_ads_booking_label,
            'generate_lead' => $this->google_ads_lead_label,
            'click_to_call' => $this->google_ads_call_label,
            'click_whatsapp' => $this->google_ads_whatsapp_label,
            default => null,
        };

        return filled($this->google_ads_conversion_id) && filled($label) ? $this->google_ads_conversion_id.'/'.$label : null;
    }
}
