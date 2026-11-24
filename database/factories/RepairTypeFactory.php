<?php

namespace Database\Factories;

use App\Models\RepairType;
use App\Models\ServiceCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<RepairType>
 */
class RepairTypeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = Str::title(fake()->unique()->words(2, true)).' Repair';

        return [
            'service_category_id' => ServiceCategory::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'summary' => fake()->sentence(),
            'icon' => 'screen',
            'typical_duration_minutes' => 45,
            'requires_diagnosis' => false,
            'is_active' => true,
            'is_popular' => false,
            'sort' => fake()->numberBetween(1, 500),
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function requiresDiagnosis(): static
    {
        return $this->state(['requires_diagnosis' => true]);
    }

    public function popular(): static
    {
        return $this->state(['is_popular' => true]);
    }
}
