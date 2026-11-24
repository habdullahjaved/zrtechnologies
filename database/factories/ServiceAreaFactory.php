<?php

namespace Database\Factories;

use App\Models\ServiceArea;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ServiceArea>
 */
class ServiceAreaFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $town = fake()->unique()->city();

        return [
            'name' => $town,
            'slug' => Str::slug($town),
            'town' => $town,
            'county' => 'Lancashire',
            'postcode_districts' => ['ZZ'.fake()->unique()->numberBetween(10, 99)],
            'centre_lat' => null,
            'centre_lng' => null,
            'radius_km' => null,
            'collection_fee_pence' => 0,
            'is_active' => true,
            'is_draft' => false,
            'sort' => fake()->numberBetween(1, 100),
        ];
    }

    /**
     * @param  list<string>  $districts
     */
    public function districts(array $districts): static
    {
        return $this->state(['postcode_districts' => $districts]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
