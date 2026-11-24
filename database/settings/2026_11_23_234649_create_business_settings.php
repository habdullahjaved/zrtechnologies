<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * Business facts from the build brief (section 1). These must match the
 * Google Business Profile exactly. Edit them in Admin → Settings → Business.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('business.legal_name', 'ZR TECHNOLOGIES LTD');
        $this->migrator->add('business.trading_name', 'ZR Technologies');
        $this->migrator->add('business.tagline', 'Innovate • Develop • Grow');
        $this->migrator->add('business.short_description', 'Expert iPhone repair with collection and delivery across Blackburn and East Lancashire. We collect your phone, repair it with quality parts and bring it back to you, with a warranty on every repair.');
        $this->migrator->add('business.company_number', '17589309');
        $this->migrator->add('business.registered_in', 'England & Wales');
        $this->migrator->add('business.registered_office_line1', '98 Moorgate Street');
        $this->migrator->add('business.registered_office_line2', null);
        $this->migrator->add('business.registered_office_town', 'Blackburn');
        $this->migrator->add('business.registered_office_county', 'Lancashire');
        $this->migrator->add('business.registered_office_postcode', 'BB2 4NY');
        $this->migrator->add('business.registered_office_country', 'United Kingdom');
        $this->migrator->add('business.director_name', 'Muhammad Umar Shabbir');
        $this->migrator->add('business.phone', '+447360127538');
        $this->migrator->add('business.whatsapp', '+447360127538');
        $this->migrator->add('business.email', 'mrumar@zrtechnologiesltd.com');
        $this->migrator->add('business.website_url', 'https://zrtechnologiesltd.com');
        $this->migrator->add('business.show_street_address', false);
        $this->migrator->add('business.service_area_summary', 'Blackburn, Darwen, Accrington and across East Lancashire');
        $this->migrator->add('business.price_range', '££');
        $this->migrator->add('business.founded_year', 2026);
        $this->migrator->add('business.ico_registration_number', null);
        $this->migrator->add('business.vat_number', null);
    }
};
