<?php

namespace Database\Factories;

use App\Models\PostcodeLookup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PostcodeLookup>
 */
class PostcodeLookupFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'postcode' => 'BB2 4NY',
            'outcode' => 'BB2',
            'is_valid' => true,
            'admin_district' => 'Blackburn with Darwen',
            'ward' => null,
            'latitude' => 53.744900,
            'longitude' => -2.489100,
            'payload' => null,
            'fetched_at' => now(),
        ];
    }
}
