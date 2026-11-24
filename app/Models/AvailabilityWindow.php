<?php

namespace App\Models;

use Database\Factories\AvailabilityWindowFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A weekly collection window, e.g. Monday 09:00–12:00 for up to 4 collections.
 *
 * @property int $weekday ISO day of week, 1 = Monday … 7 = Sunday
 * @property string $starts_at HH:MM:SS
 * @property string $ends_at HH:MM:SS
 */
#[Fillable(['weekday', 'label', 'starts_at', 'ends_at', 'capacity', 'is_active'])]
class AvailabilityWindow extends Model
{
    /** @use HasFactory<AvailabilityWindowFactory> */
    use HasFactory;

    /**
     * @var array<int, string>
     */
    public const array WEEKDAYS = [
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
        7 => 'Sunday',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'weekday' => 'integer',
            'capacity' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<AvailabilityOverride, $this>
     */
    public function overrides(): HasMany
    {
        return $this->hasMany(AvailabilityOverride::class);
    }

    public function weekdayName(): string
    {
        return self::WEEKDAYS[$this->weekday] ?? (string) $this->weekday;
    }

    /**
     * e.g. "9am–12pm" or "12:30pm–3pm".
     */
    public function timeRange(): string
    {
        return self::formatTime($this->starts_at).'–'.self::formatTime($this->ends_at);
    }

    /**
     * The window's start on a given date, in the app timezone.
     */
    public function startsOn(Carbon $date): Carbon
    {
        return $date->copy()->setTimeFromTimeString($this->starts_at);
    }

    private static function formatTime(string $time): string
    {
        $moment = Carbon::createFromFormat('H:i:s', strlen($time) === 5 ? $time.':00' : $time);

        return $moment->minute === 0 ? $moment->format('ga') : $moment->format('g:ia');
    }
}
