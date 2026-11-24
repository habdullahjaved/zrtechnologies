<?php

namespace App\Data;

use App\Models\AvailabilityWindow;
use Illuminate\Support\Carbon;

/**
 * One bookable collection window on one date.
 */
final readonly class CollectionSlot
{
    public function __construct(
        public Carbon $date,
        public AvailabilityWindow $window,
        public int $remaining,
    ) {}

    /**
     * Stable identifier for forms, e.g. "2026-11-25:3".
     */
    public function key(): string
    {
        return $this->date->toDateString().':'.$this->window->getKey();
    }

    /**
     * e.g. "Tomorrow, 9am–12pm" or "Thursday 26 November, 12pm–3pm".
     */
    public function label(): string
    {
        $day = match (true) {
            $this->date->isToday() => 'Today',
            $this->date->isTomorrow() => 'Tomorrow',
            default => $this->date->format('l j F'),
        };

        return $day.', '.$this->window->timeRange();
    }
}
