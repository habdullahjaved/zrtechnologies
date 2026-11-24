<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Name, address and phone (NAP) plus company details.
 *
 * This is the single source of truth for the header, footer, structured data,
 * emails and documents, so the details always match Google Business Profile.
 */
class BusinessSettings extends Settings
{
    public string $legal_name;

    public string $trading_name;

    public string $tagline;

    public string $short_description;

    public string $company_number;

    public string $registered_in;

    public string $registered_office_line1;

    public ?string $registered_office_line2;

    public string $registered_office_town;

    public ?string $registered_office_county;

    public string $registered_office_postcode;

    public string $registered_office_country;

    public string $director_name;

    /** Primary phone in E.164 format, e.g. +447360127538. */
    public string $phone;

    /** WhatsApp number in E.164 format. */
    public string $whatsapp;

    public string $email;

    public string $website_url;

    /** Show the street address publicly (schema and contact page). Off for a service-area business. */
    public bool $show_street_address;

    public string $service_area_summary;

    public string $price_range;

    public ?int $founded_year;

    public ?string $ico_registration_number;

    public ?string $vat_number;

    public static function group(): string
    {
        return 'business';
    }

    /**
     * The registered office as one line, e.g. for the footer company details.
     */
    public function registeredOfficeOneLine(): string
    {
        return collect([
            $this->registered_office_line1,
            $this->registered_office_line2,
            $this->registered_office_town,
            $this->registered_office_county,
            $this->registered_office_postcode,
        ])->filter()->implode(', ');
    }
}
