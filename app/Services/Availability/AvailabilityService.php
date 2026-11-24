<?php

namespace App\Services\Availability;

use App\Data\CollectionSlot;
use App\Models\AvailabilityOverride;
use App\Models\AvailabilityWindow;
use App\Models\BlackoutDate;
use App\Settings\BookingSettings;
use Illuminate\Support\Carbon;

/**
 * Works out which collection windows customers may book.
 *
 * Rules (all editable in the admin):
 *  - only active weekly windows, from today up to "max days ahead";
 *  - never on a blackout date;
 *  - a window must start at least "lead time" hours from now;
 *  - same-day bookings close at the "same-day cutoff";
 *  - a date-specific override replaces a window's capacity (0 closes it).
 *
 * Booked collections are subtracted from capacity once repair jobs exist (Phase 3).
 */
class AvailabilityService
{
    public function __construct(private readonly BookingSettings $settings) {}

    /**
     * Every bookable slot in date and time order.
     *
     * @return list<CollectionSlot>
     */
    public function upcomingSlots(?Carbon $now = null): array
    {
        $now = ($now ?? now())->copy();
        $today = $now->copy()->startOfDay();
        $lastDay = $today->copy()->addDays(max(0, $this->settings->max_days_ahead));
        $earliestStart = $now->copy()->addHours(max(0, $this->settings->lead_time_hours));
        $sameDayCutoff = $today->copy()->setTimeFromTimeString($this->settings->same_day_cutoff);

        $windowsByWeekday = AvailabilityWindow::query()
            ->where('is_active', true)
            ->orderBy('starts_at')
            ->get()
            ->groupBy('weekday');

        if ($windowsByWeekday->isEmpty()) {
            return [];
        }

        $blackouts = BlackoutDate::query()
            ->whereBetween('date', [$today->toDateString(), $lastDay->toDateString()])
            ->get()
            ->mapWithKeys(fn (BlackoutDate $blackout): array => [$blackout->date->toDateString() => true]);

        $overrides = AvailabilityOverride::query()
            ->whereBetween('date', [$today->toDateString(), $lastDay->toDateString()])
            ->get()
            ->mapWithKeys(fn (AvailabilityOverride $override): array => [
                $override->date->toDateString().':'.$override->availability_window_id => $override->capacity,
            ]);

        $slots = [];

        for ($date = $today->copy(); $date->lte($lastDay); $date->addDay()) {
            if ($blackouts->has($date->toDateString())) {
                continue;
            }

            if ($date->isSameDay($today) && $now->gte($sameDayCutoff)) {
                continue;
            }

            /** @var AvailabilityWindow $window */
            foreach ($windowsByWeekday->get($date->isoWeekday(), []) as $window) {
                if ($window->startsOn($date)->lt($earliestStart)) {
                    continue;
                }

                $capacity = $overrides->get($date->toDateString().':'.$window->getKey(), $window->capacity);

                if ($capacity <= 0) {
                    continue;
                }

                $slots[] = new CollectionSlot($date->copy(), $window, $capacity);
            }
        }

        return $slots;
    }

    public function nextSlot(?Carbon $now = null): ?CollectionSlot
    {
        return $this->upcomingSlots($now)[0] ?? null;
    }
}
