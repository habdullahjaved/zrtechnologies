<?php

namespace App\Models;

use Database\Factories\PartQualityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A grade of replacement part (e.g. Standard, Premium, Original), offered
 * where a repair can be done with more than one kind of part.
 */
#[Fillable(['name', 'slug', 'description', 'is_active', 'sort'])]
class PartQuality extends Model
{
    /** @use HasFactory<PartQualityFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<RepairPrice, $this>
     */
    public function prices(): HasMany
    {
        return $this->hasMany(RepairPrice::class);
    }
}
