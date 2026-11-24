<?php

namespace App\Models;

use App\Support\Postcode;
use Database\Factories\AddressFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A customer's collection or return address.
 *
 * @property string $line1
 * @property string|null $line2
 * @property string $town
 * @property string|null $county
 * @property string $postcode
 */
#[Fillable(['customer_id', 'label', 'line1', 'line2', 'town', 'county', 'postcode', 'latitude', 'longitude', 'is_default'])]
class Address extends Model
{
    /** @use HasFactory<AddressFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:6',
            'longitude' => 'decimal:6',
            'is_default' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return Attribute<string, string>
     */
    protected function postcode(): Attribute
    {
        return Attribute::set(fn (string $value): string => Postcode::normalise($value) ?? mb_strtoupper(trim($value)));
    }

    /**
     * e.g. "12 King Street, Blackburn, BB2 4NY".
     */
    public function oneLine(): string
    {
        return collect([$this->line1, $this->line2, $this->town, $this->postcode])->filter()->implode(', ');
    }

    /**
     * A Google Maps directions link for the driver.
     */
    public function directionsUrl(): string
    {
        return 'https://www.google.com/maps/dir/?api=1&destination='.rawurlencode($this->oneLine());
    }
}
