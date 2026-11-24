<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '+4477'.fake()->unique()->numerify('########'),
            'marketing_consent' => false,
        ];
    }

    public function withoutEmail(): static
    {
        return $this->state(['email' => null]);
    }

    public function marketingOptIn(): static
    {
        return $this->state(['marketing_consent' => true, 'marketing_consent_at' => now(), 'marketing_consent_source' => 'booking']);
    }
}
