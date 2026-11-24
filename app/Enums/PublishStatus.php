<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Editorial workflow for pages and blog posts. A scheduled item goes live on
 * its publish date; until then it behaves like a draft.
 */
enum PublishStatus: string implements HasColor, HasLabel
{
    case Draft = 'draft';
    case Review = 'review';
    case Scheduled = 'scheduled';
    case Published = 'published';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Review => 'Ready for review',
            self::Scheduled => 'Scheduled',
            self::Published => 'Published',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Review => 'warning',
            self::Scheduled => 'info',
            self::Published => 'success',
        };
    }

    /**
     * Statuses that are visible once the publish date has passed.
     *
     * @return list<self>
     */
    public static function live(): array
    {
        return [self::Scheduled, self::Published];
    }
}
