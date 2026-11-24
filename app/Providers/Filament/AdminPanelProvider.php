<?php

namespace App\Providers\Filament;

use App\Filament\NavigationGroup;
use App\Http\Middleware\EnsureStaffMultiFactorAuthentication;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Auth\MultiFactor\Email\EmailAuthentication;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path(config('zr.admin.path'))
            ->brandName('ZR Technologies')
            ->brandLogo(asset('brand/zr-logo-horizontal-colour-480w.webp'))
            ->darkModeBrandLogo(asset('brand/zr-logo-horizontal-for-dark-bg-480w.webp'))
            ->brandLogoHeight('2.25rem')
            ->favicon(asset('favicon-32x32.png'))
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->colors([
                // Exact brand scale so buttons use royal #0058B0 (600) with white text (6.95:1).
                'primary' => [
                    50 => '#EEF5FF',
                    100 => '#DCEBFF',
                    200 => '#B3DCFB',
                    300 => '#7CC2F8',
                    400 => '#3FA9F5',
                    500 => '#1E6FD9',
                    600 => '#0058B0',
                    700 => '#004A95',
                    800 => '#0A3A78',
                    900 => '#0A1F4E',
                    950 => '#00163F',
                ],
                'gray' => Color::Slate,
                'info' => Color::hex('#1E6FD9'),
                'success' => Color::Green,
                'warning' => Color::Amber,
                'danger' => Color::Red,
            ])
            ->login()
            ->passwordReset()
            ->profile(isSimple: false)
            ->multiFactorAuthentication(
                providers: [
                    AppAuthentication::make()
                        ->brandName('ZR Technologies')
                        ->recoverable(),
                    EmailAuthentication::make()
                        ->codeExpiryMinutes(10),
                ],
                isRequired: fn (): bool => (bool) config('zr.admin.require_mfa'),
            )
            ->multiFactorAuthenticationRequiredMiddlewareName(EnsureStaffMultiFactorAuthentication::class)
            ->spa()
            ->unsavedChangesAlerts()
            ->sidebarCollapsibleOnDesktop()
            ->maxContentWidth(Width::SevenExtraLarge)
            ->globalSearchKeyBindings(['mod+k'])
            ->navigationGroups(NavigationGroup::class)
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
            ])
            ->plugins([
                FilamentShieldPlugin::make()
                    ->navigationGroup(NavigationGroup::System)
                    ->navigationLabel('Roles & permissions')
                    ->navigationIcon(Heroicon::OutlinedShieldCheck)
                    ->navigationSort(20),
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
