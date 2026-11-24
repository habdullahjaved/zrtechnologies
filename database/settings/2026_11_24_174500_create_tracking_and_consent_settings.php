<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * Phase 5: tracking IDs (all empty until the owner adds them), the cookie
 * banner wording and the review-request switch.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('tracking.gtm_container_id', null);
        $this->migrator->add('tracking.ga4_measurement_id', null);
        $this->migrator->addEncrypted('tracking.ga4_api_secret', null);
        $this->migrator->add('tracking.google_ads_conversion_id', null);
        $this->migrator->add('tracking.google_ads_booking_label', null);
        $this->migrator->add('tracking.google_ads_lead_label', null);
        $this->migrator->add('tracking.google_ads_call_label', null);
        $this->migrator->add('tracking.google_ads_whatsapp_label', null);
        $this->migrator->add('tracking.google_ads_offline_conversion_name', 'Repair Completed');
        $this->migrator->add('tracking.enhanced_conversions', true);
        $this->migrator->add('tracking.meta_pixel_id', null);
        $this->migrator->addEncrypted('tracking.meta_capi_token', null);
        $this->migrator->add('tracking.meta_test_event_code', null);
        $this->migrator->add('tracking.clarity_project_id', null);
        $this->migrator->add('tracking.tiktok_pixel_id', null);
        $this->migrator->add('tracking.test_mode', false);
        $this->migrator->addEncrypted('tracking.export_token', bin2hex(random_bytes(24)));

        $this->migrator->add('consent.policy_version', '2026-11');
        $this->migrator->add('consent.banner_title', 'Cookies on our website');
        $this->migrator->add('consent.banner_text', 'We use essential cookies to make our booking and enquiry forms work. With your permission we would also like to use analytics cookies to understand how our website is used, and marketing cookies to measure our adverts. You can change your mind at any time.');
        $this->migrator->add('consent.analytics_description', 'Help us see which pages are useful and where the website could be easier to use (Google Analytics, Microsoft Clarity).');
        $this->migrator->add('consent.marketing_description', 'Let us measure which adverts bring customers to us (Google Ads, Meta and TikTok), so we spend less on advertising that does not work.');

        $this->migrator->add('notifications.send_review_requests', true);
    }
};
