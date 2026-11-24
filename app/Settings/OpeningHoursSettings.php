<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Opening hours: one source of truth for the footer, contact page and schema.
 *
 * Each day is stored as ['is_open' => bool, 'opens' => 'HH:MM', 'closes' => 'HH:MM'].
 */
class OpeningHoursSettings extends Settings
{
    /**
     * Keyed by day (monday … sunday); each value holds is_open, opens and closes ("HH:MM" or null).
     * (@phpstan-var rather than @var: spatie/laravel-settings cannot parse array shapes.)
     *
     * @phpstan-var array<string, array{is_open: bool, opens: string|null, closes: string|null}>
     */
    public array $days;

    public ?string $note;

    public const array DAYS = [
        'monday' => 'Monday',
        'tuesday' => 'Tuesday',
        'wednesday' => 'Wednesday',
        'thursday' => 'Thursday',
        'friday' => 'Friday',
        'saturday' => 'Saturday',
        'sunday' => 'Sunday',
    ];

    public static function group(): string
    {
        return 'opening_hours';
    }

    /**
     * Consecutive days with identical hours grouped for display,
     * e.g. ["Monday – Friday" => "9:00am – 7:00pm", "Sunday" => "Closed"].
     *
     * @return array<string, string>
     */
    public function summary(): array
    {
        $groups = [];
        $previousKey = null;

        foreach (self::DAYS as $day => $label) {
            $hours = $this->days[$day] ?? ['is_open' => false, 'opens' => null, 'closes' => null];
            $text = $hours['is_open'] && $hours['opens'] && $hours['closes']
                ? self::formatTime($hours['opens']).' – '.self::formatTime($hours['closes'])
                : 'Closed';

            if ($previousKey !== null && $groups[$previousKey]['text'] === $text) {
                $groups[$previousKey]['last'] = $label;

                continue;
            }

            $previousKey = $day;
            $groups[$day] = ['first' => $label, 'last' => $label, 'text' => $text];
        }

        $summary = [];

        foreach ($groups as $group) {
            $label = $group['first'] === $group['last'] ? $group['first'] : $group['first'].' – '.$group['last'];
            $summary[$label] = $group['text'];
        }

        return $summary;
    }

    /**
     * Format "09:00" as "9:00am" and "19:30" as "7:30pm".
     */
    public static function formatTime(string $time): string
    {
        [$hour, $minute] = array_map('intval', explode(':', $time));
        $suffix = $hour >= 12 ? 'pm' : 'am';
        $displayHour = $hour % 12 === 0 ? 12 : $hour % 12;

        return sprintf('%d:%02d%s', $displayHour, $minute, $suffix);
    }
}
