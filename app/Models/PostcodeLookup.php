<?php

namespace App\Models;

use Database\Factories\PostcodeLookupFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A cached postcodes.io answer for one full postcode.
 *
 * @property Carbon $fetched_at
 * @property string|null $latitude
 * @property string|null $longitude
 */
#[Fillable(['postcode', 'outcode', 'is_valid', 'admin_district', 'ward', 'latitude', 'longitude', 'payload', 'fetched_at'])]
class PostcodeLookup extends Model
{
    /** @use HasFactory<PostcodeLookupFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_valid' => 'boolean',
            'latitude' => 'decimal:6',
            'longitude' => 'decimal:6',
            'payload' => 'array',
            'fetched_at' => 'datetime',
        ];
    }
}
