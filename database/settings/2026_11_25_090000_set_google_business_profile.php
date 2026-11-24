<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * The owner's Google Business Profile. Used in the business structured data
 * (sameAs, hasMap), the footer and Tracking health. Only filled in if empty,
 * so a link changed in Settings → Social is kept.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->update(
            'social.google_business_profile',
            fn (?string $value): string => filled($value) ? $value : 'https://share.google/rC3I3iXS9seMC8iDk',
        );
    }
};
