<?php

namespace App\Filament;

use Filament\Support\Contracts\HasLabel;

/**
 * Admin sidebar groups, in display order. Each phase adds resources to its group.
 */
enum NavigationGroup: string implements HasLabel
{
    case Crm = 'crm';
    case Workshop = 'workshop';
    case Catalogue = 'catalogue';
    case Coverage = 'coverage';
    case Content = 'content';
    case Seo = 'seo';
    case Marketing = 'marketing';
    case Reports = 'reports';
    case Settings = 'settings';
    case System = 'system';

    public function getLabel(): string
    {
        return match ($this) {
            self::Crm => 'CRM',
            self::Workshop => 'Workshop',
            self::Catalogue => 'Catalogue',
            self::Coverage => 'Coverage',
            self::Content => 'Content',
            self::Seo => 'SEO',
            self::Marketing => 'Marketing',
            self::Reports => 'Reports',
            self::Settings => 'Settings',
            self::System => 'Users & security',
        };
    }
}
