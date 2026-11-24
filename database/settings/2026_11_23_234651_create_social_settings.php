<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * Social profiles start empty: nothing is shown until a real profile URL is added
 * in Admin → Settings → Social & listings.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        foreach (['google_business_profile', 'facebook', 'instagram', 'tiktok', 'youtube', 'linkedin', 'x', 'ebay_store'] as $network) {
            $this->migrator->add("social.{$network}", null);
        }
    }
};
