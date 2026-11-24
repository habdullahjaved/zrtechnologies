<?php

namespace Database\Factories;

use App\Models\AvailabilityWindow;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AvailabilityWindow>
 */
class AvailabilityWindowFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'weekday' => 1,
            'label' => 'Morning',
            'starts_at' => '09:00:00',
            'ends_at' => '12:00:00',
            'capacity' => 3,
            'is_active' => true,
        ];
    }
}
