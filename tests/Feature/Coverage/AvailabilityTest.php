<?php

use App\Data\CollectionSlot;
use App\Models\AvailabilityOverride;
use App\Models\AvailabilityWindow;
use App\Models\BlackoutDate;
use App\Services\Availability\AvailabilityService;
use App\Settings\BookingSettings;
use Illuminate\Support\Carbon;

/**
 * @return list<string> e.g. "2026-11-30 09:00"
 */
function slotKeys(?Carbon $now = null): array
{
    return array_map(
        fn (CollectionSlot $slot): string => $slot->date->toDateString().' '.substr($slot->window->starts_at, 0, 5),
        app(AvailabilityService::class)->upcomingSlots($now),
    );
}

beforeEach(function () {
    $settings = app(BookingSettings::class);
    $settings->lead_time_hours = 2;
    $settings->same_day_cutoff = '12:00';
    $settings->max_days_ahead = 7;
    $settings->save();

    // Monday to Friday: morning and afternoon. No weekend windows.
    foreach (range(1, 5) as $weekday) {
        AvailabilityWindow::factory()->create(['weekday' => $weekday, 'label' => 'Morning', 'starts_at' => '09:00', 'ends_at' => '12:00', 'capacity' => 3]);
        AvailabilityWindow::factory()->create(['weekday' => $weekday, 'label' => 'Afternoon', 'starts_at' => '13:00', 'ends_at' => '16:00', 'capacity' => 3]);
    }
});

afterEach(fn () => Carbon::setTestNow());

it('offers windows from today up to the booking horizon, skipping days without windows', function () {
    Carbon::setTestNow('2026-11-30 06:00'); // Monday

    expect(slotKeys())->toBe([
        '2026-11-30 09:00', '2026-11-30 13:00',
        '2026-12-01 09:00', '2026-12-01 13:00',
        '2026-12-02 09:00', '2026-12-02 13:00',
        '2026-12-03 09:00', '2026-12-03 13:00',
        '2026-12-04 09:00', '2026-12-04 13:00',
        '2026-12-07 09:00', '2026-12-07 13:00',
    ]);
});

it('needs the minimum notice before a window starts', function () {
    Carbon::setTestNow('2026-11-30 07:30'); // Monday, 90 minutes before the morning window

    expect(array_slice(slotKeys(), 0, 2))->toBe(['2026-11-30 13:00', '2026-12-01 09:00']);
});

it('closes same-day booking at the cutoff', function () {
    Carbon::setTestNow('2026-11-30 12:00'); // Monday, afternoon window still 3 hours away

    expect(slotKeys()[0])->toBe('2026-12-01 09:00');
});

it('skips blackout dates', function () {
    Carbon::setTestNow('2026-11-30 06:00');
    BlackoutDate::factory()->create(['date' => '2026-12-01', 'reason' => 'Training']);

    expect(slotKeys())->not->toContain('2026-12-01 09:00')
        ->and(slotKeys())->not->toContain('2026-12-01 13:00')
        ->and(slotKeys())->toContain('2026-12-02 09:00');
});

it('applies date-specific capacity changes, where zero closes a window', function () {
    Carbon::setTestNow('2026-11-30 06:00');
    $tuesdayMorning = AvailabilityWindow::query()->where('weekday', 2)->where('label', 'Morning')->sole();
    $tuesdayAfternoon = AvailabilityWindow::query()->where('weekday', 2)->where('label', 'Afternoon')->sole();

    AvailabilityOverride::factory()->for($tuesdayMorning)->create(['date' => '2026-12-01', 'capacity' => 0]);
    AvailabilityOverride::factory()->for($tuesdayAfternoon)->create(['date' => '2026-12-01', 'capacity' => 8]);

    $tuesday = collect(app(AvailabilityService::class)->upcomingSlots())
        ->filter(fn (CollectionSlot $slot): bool => $slot->date->toDateString() === '2026-12-01');

    expect($tuesday)->toHaveCount(1)
        ->and($tuesday->first()->window->is($tuesdayAfternoon))->toBeTrue()
        ->and($tuesday->first()->remaining)->toBe(8);
});

it('ignores windows that are switched off', function () {
    Carbon::setTestNow('2026-11-30 06:00');
    AvailabilityWindow::query()->where('label', 'Afternoon')->update(['is_active' => false]);

    expect(slotKeys())->each->toEndWith('09:00');
});

it('labels slots for customers', function () {
    Carbon::setTestNow('2026-11-30 06:00');

    $slots = app(AvailabilityService::class)->upcomingSlots();

    expect($slots[0]->label())->toBe('Today, 9am–12pm')
        ->and($slots[2]->label())->toBe('Tomorrow, 9am–12pm')
        ->and($slots[4]->label())->toBe('Wednesday 2 December, 9am–12pm')
        ->and($slots[0]->key())->toBe('2026-11-30:'.$slots[0]->window->id);
});

it('returns no slots when the timetable is empty', function () {
    AvailabilityWindow::query()->delete();

    expect(app(AvailabilityService::class)->nextSlot())->toBeNull();
});
