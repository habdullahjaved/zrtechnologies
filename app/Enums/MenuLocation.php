<?php

namespace App\Enums;

use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;

/**
 * Editable menus. The "iPhone Repair" mega menu and the footer repair and
 * area columns are built from the catalogue, so they are not listed here.
 */
enum MenuLocation: string implements HasDescription, HasLabel
{
    case Header = 'header';
    case FooterCompany = 'footer_company';
    case FooterLegal = 'footer_legal';

    public function getLabel(): string
    {
        return match ($this) {
            self::Header => 'Header',
            self::FooterCompany => 'Footer: company',
            self::FooterLegal => 'Footer: legal',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Header => 'Main links across the top of every page (after "iPhone Repair").',
            self::FooterCompany => 'The "Company" column in the footer.',
            self::FooterLegal => 'The row of legal links at the bottom of every page.',
        };
    }
}
