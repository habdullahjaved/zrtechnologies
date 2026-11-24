<?php

namespace Database\Factories;

use App\Enums\DisplayType;
use App\Models\DeviceBrand;
use App\Models\DeviceModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<DeviceModel>
 */
class DeviceModelFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = 'iPhone '.fake()->unique()->numberBetween(20, 999);

        return [
            'device_brand_id' => DeviceBrand::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'family' => $name,
            'release_year' => fake()->numberBetween(2017, 2026),
            'display_type' => DisplayType::Oled,
            'is_active' => true,
            'is_popular' => false,
            'sort' => fake()->numberBetween(1, 500),
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function popular(): static
    {
        return $this->state(['is_popular' => true]);
    }
}
