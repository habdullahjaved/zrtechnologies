<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Sales pipeline stages (brief §12): New → Contacted → Quoted → Booked → Collected → Completed, or Lost.
 */
enum LeadStatus: string implements HasColor, HasLabel
{
    case New = 'new';
    case Contacted = 'contacted';
    case Quoted = 'quoted';
    case Booked = 'booked';
    case Collected = 'collected';
    case Completed = 'completed';
    case Lost = 'lost';

    public function getLabel(): string
    {
        return ucfirst($this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::New => 'warning',
            self::Contacted, self::Quoted => 'info',
            self::Booked, self::Collected => 'primary',
            self::Completed => 'success',
            self::Lost => 'gray',
        };
    }

    /**
     * Still needs someone to act on it.
     */
    public function isOpen(): bool
    {
        return in_array($this, [self::New, self::Contacted, self::Quoted], true);
    }

    /**
     * @return list<self>
     */
    public static function open(): array
    {
        return array_values(array_filter(self::cases(), fn (self $status): bool => $status->isOpen()));
    }
}
