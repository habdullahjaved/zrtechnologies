<?php

namespace Database\Factories;

use App\Models\DeviceModel;
use App\Models\RepairPrice;
use App\Models\RepairType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RepairPrice>
 */
class RepairPriceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'device_model_id' => DeviceModel::factory(),
            'repair_type_id' => RepairType::factory(),
            'part_quality_id' => null,
            'price_pence' => fake()->numberBetween(30, 300) * 100,
            'sale_price_pence' => null,
            'warranty_days' => 90,
            'in_stock' => true,
            'is_active' => true,
            'is_draft' => false,
        ];
    }

    public function draft(): static
    {
        return $this->state(['is_draft' => true]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
