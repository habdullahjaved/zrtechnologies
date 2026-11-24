<?php

namespace App\Enums;

use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;

/**
 * Staff roles. The values are the role names stored by spatie/laravel-permission.
 */
enum StaffRole: string implements HasDescription, HasLabel
{
    case SuperAdmin = 'super_admin';
    case Manager = 'manager';
    case Technician = 'technician';
    case ContentEditor = 'content_editor';
    case SalesSupport = 'sales_support';

    public function getLabel(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::Manager => 'Manager',
            self::Technician => 'Technician',
            self::ContentEditor => 'Marketing / Content',
            self::SalesSupport => 'Sales / Support',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Full access to everything, including users, roles and settings.',
            self::Manager => 'Runs the business day to day: CRM, jobs, catalogue, content and settings. Cannot manage roles.',
            self::Technician => 'Sees and updates repair jobs. No access to financials, marketing or settings.',
            self::ContentEditor => 'Website content, blog, videos, FAQs and SEO.',
            self::SalesSupport => 'Leads, customers and enquiries.',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $role) {
            $options[$role->value] = $role->getLabel();
        }

        return $options;
    }
}
