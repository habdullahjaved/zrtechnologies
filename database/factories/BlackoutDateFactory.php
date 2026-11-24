<?php

namespace Database\Factories;

use App\Models\BlackoutDate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BlackoutDate>
 */
class BlackoutDateFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'date' => today()->addDays(fake()->unique()->numberBetween(1, 300)),
            'reason' => 'Closed',
        ];
    }
}
