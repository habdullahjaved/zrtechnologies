<?php

namespace App\Models;

use Database\Factories\BlackoutDateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A whole day with no collections, e.g. a bank holiday.
 *
 * @property Carbon $date
 */
#[Fillable(['date', 'reason'])]
class BlackoutDate extends Model
{
    /** @use HasFactory<BlackoutDateFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }
}
