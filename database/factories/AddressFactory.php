<?php

namespace Database\Factories;

use App\Models\Address;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Address>
 */
class AddressFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'line1' => fake()->buildingNumber().' '.fake()->streetName(),
            'line2' => null,
            'town' => 'Blackburn',
            'postcode' => 'BB2 4NY',
            'is_default' => true,
        ];
    }

    public function postcode(string $postcode): static
    {
        return $this->state(['postcode' => $postcode]);
    }
}
