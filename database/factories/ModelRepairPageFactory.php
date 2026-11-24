<?php

namespace Database\Factories;

use App\Models\DeviceModel;
use App\Models\ModelRepairPage;
use App\Models\RepairType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ModelRepairPage>
 */
class ModelRepairPageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'device_model_id' => DeviceModel::factory(),
            'repair_type_id' => RepairType::factory(),
            'intro' => fake()->sentence(20),
            'blocks' => [['type' => 'rich_text', 'data' => ['content' => '<p>'.fake()->paragraph(3).'</p>']]],
            'is_enabled' => true,
        ];
    }
}
