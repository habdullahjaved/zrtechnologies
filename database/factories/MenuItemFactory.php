<?php

namespace Database\Factories;

use App\Enums\MenuLocation;
use App\Models\MenuItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MenuItem>
 */
class MenuItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'menu' => MenuLocation::Header,
            'label' => ucfirst(fake()->unique()->word()),
            'url' => '/'.fake()->slug(1),
            'opens_in_new_tab' => false,
            'is_active' => true,
            'sort' => fake()->numberBetween(1, 100),
        ];
    }
}
