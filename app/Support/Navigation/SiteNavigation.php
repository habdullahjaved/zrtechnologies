<?php

namespace App\Support\Navigation;

use Illuminate\Support\Facades\Route;

/**
 * Public site navigation. Items appear only once their page's route exists, so
 * the menu fills in as each phase ships and never contains a dead link.
 */
final class SiteNavigation
{
    /**
     * Header links in display order (brief §4: Navigation).
     *
     * @var list<array{label: string, route: string}>
     */
    private const array PRIMARY = [
        ['label' => 'iPhone Repair', 'route' => 'repairs.index'],
        ['label' => 'Prices', 'route' => 'prices'],
        ['label' => 'How It Works', 'route' => 'how-it-works'],
        ['label' => 'Areas', 'route' => 'areas.index'],
        ['label' => 'Blog', 'route' => 'blog.index'],
        ['label' => 'Contact', 'route' => 'contact'],
    ];

    /**
     * Footer "Company" and legal links.
     *
     * @var list<array{label: string, route: string}>
     */
    private const array FOOTER_COMPANY = [
        ['label' => 'About us', 'route' => 'about'],
        ['label' => 'Reviews', 'route' => 'reviews'],
        ['label' => 'FAQs', 'route' => 'faqs'],
        ['label' => 'Warranty', 'route' => 'warranty'],
        ['label' => 'Track a repair', 'route' => 'track-repair'],
        ['label' => 'Business repairs', 'route' => 'business'],
    ];

    /**
     * @var list<array{label: string, route: string}>
     */
    private const array FOOTER_LEGAL = [
        ['label' => 'Privacy policy', 'route' => 'legal.privacy'],
        ['label' => 'Cookie policy', 'route' => 'legal.cookies'],
        ['label' => 'Terms & conditions', 'route' => 'legal.terms'],
        ['label' => 'Repair terms', 'route' => 'legal.repair-terms'],
        ['label' => 'Accessibility', 'route' => 'legal.accessibility'],
    ];

    /**
     * @return list<array{label: string, url: string, active: bool}>
     */
    public function primary(): array
    {
        return $this->resolve(self::PRIMARY);
    }

    /**
     * @return list<array{label: string, url: string, active: bool}>
     */
    public function footerCompany(): array
    {
        return $this->resolve(self::FOOTER_COMPANY);
    }

    /**
     * @return list<array{label: string, url: string, active: bool}>
     */
    public function footerLegal(): array
    {
        return $this->resolve(self::FOOTER_LEGAL);
    }

    /**
     * @param  list<array{label: string, route: string}>  $items
     * @return list<array{label: string, url: string, active: bool}>
     */
    private function resolve(array $items): array
    {
        $links = [];

        foreach ($items as $item) {
            if (! Route::has($item['route'])) {
                continue;
            }

            $links[] = [
                'label' => $item['label'],
                'url' => route($item['route']),
                'active' => request()->routeIs($item['route'], $item['route'].'.*'),
            ];
        }

        return $links;
    }
}
