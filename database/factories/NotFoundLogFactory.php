<?php

namespace Database\Factories;

use App\Models\NotFoundLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NotFoundLog>
 */
class NotFoundLogFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'path' => '/'.fake()->unique()->slug(3),
            'hits' => fake()->numberBetween(1, 40),
            'last_referrer' => 'https://www.google.com/',
            'last_seen_at' => now(),
        ];
    }
}
