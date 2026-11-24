<?php

namespace App\Models;

use Database\Factories\DeviceBrandFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A device manufacturer. Apple first; other brands can be added later.
 */
#[Fillable(['name', 'slug', 'is_active', 'sort'])]
class DeviceBrand extends Model
{
    /** @use HasFactory<DeviceBrandFactory> */
    use HasFactory;

    public const string APPLE = 'apple';

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
     * @return HasMany<DeviceModel, $this>
     */
    public function deviceModels(): HasMany
    {
        return $this->hasMany(DeviceModel::class);
    }
}
