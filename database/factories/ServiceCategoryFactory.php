<?php

namespace Database\Factories;

use App\Enums\FormType;
use App\Models\ServiceCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ServiceCategory>
 */
class ServiceCategoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = Str::title(fake()->unique()->words(2, true));

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'form_type' => FormType::Booking,
            'is_active' => true,
            'sort' => fake()->numberBetween(1, 100),
        ];
    }
}
