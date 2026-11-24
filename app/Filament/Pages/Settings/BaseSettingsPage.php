<?php

namespace App\Filament\Pages\Settings;

use App\Filament\NavigationGroup;
use Filament\Pages\SettingsPage;
use UnitEnum;

/**
 * Shared behaviour for every settings screen, grouped under "Settings" in the sidebar.
 *
 * Each concrete page uses HasPageShield itself: the trait caches its permission
 * key in a static property, which would otherwise be shared by every subclass.
 */
abstract class BaseSettingsPage extends SettingsPage
{
    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Settings;

    public function getSavedNotificationTitle(): ?string
    {
        return 'Settings saved';
    }
}
