<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Where a genuine review was left.
 */
enum ReviewSource: string implements HasLabel
{
    case Google = 'google';
    case Facebook = 'facebook';
    case Trustpilot = 'trustpilot';
    case Website = 'website';

    public function getLabel(): string
    {
        return match ($this) {
            self::Google => 'Google',
            self::Facebook => 'Facebook',
            self::Trustpilot => 'Trustpilot',
            self::Website => 'Direct to us',
        };
    }
}
