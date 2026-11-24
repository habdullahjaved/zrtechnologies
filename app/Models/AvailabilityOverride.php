<?php

namespace App\Models;

use Database\Factories\AvailabilityOverrideFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Changes one window's capacity on one date (0 closes the window that day).
 *
 * @property Carbon $date
 */
#[Fillable(['date', 'availability_window_id', 'capacity', 'note'])]
class AvailabilityOverride extends Model
{
    /** @use HasFactory<AvailabilityOverrideFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'capacity' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<AvailabilityWindow, $this>
     */
    public function availabilityWindow(): BelongsTo
    {
        return $this->belongsTo(AvailabilityWindow::class);
    }
}
