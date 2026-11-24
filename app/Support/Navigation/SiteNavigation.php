<?php

namespace App\Support\Navigation;

use App\Enums\MenuLocation;
use App\Models\DeviceModel;
use App\Models\MenuItem;
use App\Models\ServiceArea;
use App\Services\Content\ServiceDirectory;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Route;

/**
 * Public site navigation.
 *
 * The header and footer link lists are edited in the admin (Content → Menus).
 * Until a menu has links of its own, the defaults below are used; each
 * default only appears once its page exists, so a menu never has a dead link.
 * The "iPhone Repair" mega menu and the footer repair and area columns are
 * built from the catalogue.
 */
final class SiteNavigation
{
    /**
     * Header links after "iPhone Repair" (brief §4: Navigation).
     *
     * @var list<array{label: string, route: string}>
     */
    public const array DEFAULT_HEADER = [
        ['label' => 'Prices', 'route' => 'prices'],
        ['label' => 'How It Works', 'route' => 'how-it-works'],
        ['label' => 'Areas', 'route' => 'areas.index'],
        ['label' => 'Blog', 'route' => 'blog.index'],
        ['label' => 'Contact', 'route' => 'contact'],
    ];

    /**
     * @var list<array{label: string, route: string}>
     */
    public const array DEFAULT_FOOTER_COMPANY = [
        ['label' => 'About us', 'route' => 'about'],
        ['label' => 'How it works', 'route' => 'how-it-works'],
        ['label' => 'Reviews', 'route' => 'reviews'],
        ['label' => 'FAQs', 'route' => 'faqs'],
        ['label' => 'Warranty', 'route' => 'warranty'],
        ['label' => 'Videos', 'route' => 'videos.index'],
        ['label' => 'Track a repair', 'route' => 'track-repair'],
        ['label' => 'Business repairs', 'route' => 'business'],
    ];

    /**
     * @var list<array{label: string, route: string}>
     */
    public const array DEFAULT_FOOTER_LEGAL = [
        ['label' => 'Privacy policy', 'route' => 'legal.privacy'],
        ['label' => 'Cookie policy', 'route' => 'legal.cookies'],
        ['label' => 'Terms & conditions', 'route' => 'legal.terms'],
        ['label' => 'Repair terms', 'route' => 'legal.repair-terms'],
        ['label' => 'Accessibility', 'route' => 'legal.accessibility'],
    ];

    /** @var array<string, Collection<int, MenuItem>>|null */
    private ?array $menus = null;

    public function __construct(private readonly ServiceDirectory $directory) {}

    /**
     * @return list<array{label: string, url: string, active: bool, external: bool}>
     */
    public function primary(): array
    {
        return $this->menu(MenuLocation::Header, self::DEFAULT_HEADER);
    }

    /**
     * @return list<array{label: string, url: string, active: bool, external: bool}>
     */
    public function footerCompany(): array
    {
        return $this->menu(MenuLocation::FooterCompany, self::DEFAULT_FOOTER_COMPANY);
    }

    /**
     * @return list<array{label: string, url: string, active: bool, external: bool}>
     */
    public function footerLegal(): array
    {
        return $this->menu(MenuLocation::FooterLegal, self::DEFAULT_FOOTER_LEGAL);
    }

    /**
     * The "iPhone Repair" mega menu: every repair page, popular models and the price list.
     *
     * @return array{url: string, active: bool, services: list<array{label: string, url: string, icon: string|null, from: string|null}>, models: list<array{label: string, url: string}>, prices_url: string|null}|null
     */
    public function megaMenu(): ?array
    {
        if (! Route::has('repairs.index')) {
            return null;
        }

        $services = [];

        foreach ($this->directory->services() as $service) {
            $from = $this->directory->fromPrice($service);
            $services[] = [
                'label' => $service->name,
                'url' => $service->url(),
                'icon' => $service->icon,
                'from' => $from !== null ? Money::format($from) : null,
            ];
        }

        $models = DeviceModel::query()->listed()
            ->where('is_popular', true)
            ->whereHas('prices', fn (Builder $prices) => $prices->available())
            ->limit(8)
            ->get(['id', 'name', 'slug'])
            ->map(fn (DeviceModel $model): array => ['label' => $model->name, 'url' => $model->url()])
            ->all();

        return [
            'url' => route('repairs.index'),
            'active' => request()->routeIs('repairs.*'),
            'services' => $services,
            'models' => $models,
            'prices_url' => Route::has('prices') ? route('prices') : null,
        ];
    }

    /**
     * Footer "Repairs" column.
     *
     * @return list<array{label: string, url: string}>
     */
    public function footerServices(): array
    {
        return $this->directory->services()
            ->map(fn ($service): array => ['label' => $service->name, 'url' => $service->url()])
            ->values()
            ->all();
    }

    /**
     * Footer "Areas" column: towns with a location page.
     *
     * @return list<array{label: string, url: string}>
     */
    public function footerAreas(): array
    {
        if (! Route::has('locations.show')) {
            return [];
        }

        return ServiceArea::query()->listed()->get(['id', 'name', 'town', 'slug'])
            ->map(fn (ServiceArea $area): array => ['label' => "iPhone repair {$area->town}", 'url' => $area->url()])
            ->all();
    }

    /**
     * @param  list<array{label: string, route: string}>  $defaults
     * @return list<array{label: string, url: string, active: bool, external: bool}>
     */
    private function menu(MenuLocation $location, array $defaults): array
    {
        $items = $this->stored()[$location->value] ?? new Collection;

        if ($items->isEmpty()) {
            return $this->defaults($defaults);
        }

        $currentPath = '/'.trim(request()->path(), '/');

        return $items->map(function (MenuItem $item) use ($currentPath): array {
            $path = '/'.trim((string) parse_url($item->href(), PHP_URL_PATH), '/');

            return [
                'label' => $item->label,
                'url' => $item->href(),
                'active' => ! $item->isExternal() && ($path === $currentPath || ($path !== '/' && str_starts_with($currentPath, $path.'/'))),
                'external' => $item->opens_in_new_tab,
            ];
        })->values()->all();
    }

    /**
     * @return array<string, Collection<int, MenuItem>>
     */
    private function stored(): array
    {
        return $this->menus ??= MenuItem::query()
            ->where('is_active', true)
            ->orderBy('sort')
            ->orderBy('id')
            ->get()
            ->groupBy(fn (MenuItem $item): string => $item->menu->value)
            ->all();
    }

    /**
     * @param  list<array{label: string, route: string}>  $items
     * @return list<array{label: string, url: string, active: bool, external: bool}>
     */
    private function defaults(array $items): array
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
                'external' => false,
            ];
        }

        return $links;
    }
}
