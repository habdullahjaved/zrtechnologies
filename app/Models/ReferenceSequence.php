<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * A running number per sequence and year. Use App\Support\ReferenceGenerator, not this model directly.
 *
 * @property string $name
 * @property int $year
 * @property int $last_number
 */
#[Fillable(['name', 'year', 'last_number'])]
class ReferenceSequence extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'last_number' => 'integer',
        ];
    }
}
