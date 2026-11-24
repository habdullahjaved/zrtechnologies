<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Screen technology fitted to a device model, which affects screen part options and price.
 */
enum DisplayType: string implements HasLabel
{
    case Lcd = 'lcd';
    case Oled = 'oled';

    public function getLabel(): string
    {
        return match ($this) {
            self::Lcd => 'LCD (Liquid Retina)',
            self::Oled => 'OLED (Super Retina)',
        };
    }
}
