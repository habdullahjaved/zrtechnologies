<?php

namespace Database\Factories;

use App\Models\RepairJob;
use App\Models\RepairJobItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RepairJobItem>
 */
class RepairJobItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'repair_job_id' => RepairJob::factory(),
            'description' => 'Screen Replacement (Standard)',
            'price_pence' => 8900,
            'warranty_days' => 90,
            'sort' => 0,
        ];
    }
}
