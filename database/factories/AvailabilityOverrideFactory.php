<?php

namespace Database\Factories;

use App\Models\AvailabilityOverride;
use App\Models\AvailabilityWindow;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AvailabilityOverride>
 */
class AvailabilityOverrideFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'date' => today()->addDay(),
            'availability_window_id' => AvailabilityWindow::factory(),
            'capacity' => 0,
            'note' => null,
        ];
    }
}
