<?php

namespace Database\Factories;

use App\Enums\ReviewSource;
use App\Models\Review;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'source' => ReviewSource::Google,
            'reviewer_name' => fake()->name(),
            'rating' => fake()->numberBetween(4, 5),
            'body' => fake()->sentences(3, true),
            'reviewed_at' => fake()->dateTimeBetween('-6 months'),
            'is_verified' => true,
            'is_published' => true,
            'is_sample' => false,
        ];
    }

    /**
     * A clearly labelled sample (local development only; never shown in production).
     */
    public function sample(): static
    {
        return $this->state(['is_sample' => true, 'is_verified' => false, 'source' => ReviewSource::Website]);
    }
}
