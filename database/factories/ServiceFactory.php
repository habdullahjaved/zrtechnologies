<?php

namespace Database\Factories;

use App\Models\RepairType;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = Str::title(fake()->unique()->words(2, true)).' Repair';

        return [
            'service_category_id' => fn () => ServiceCategory::query()->where('slug', ServiceCategory::IPHONE_REPAIR)->value('id')
                ?? ServiceCategory::factory()->create(['name' => 'iPhone Repair', 'slug' => ServiceCategory::IPHONE_REPAIR])->getKey(),
            'name' => $name,
            'slug' => Str::slug($name),
            'summary' => fake()->sentence(14),
            'intro' => fake()->sentence(20),
            'blocks' => [
                ['type' => 'heading', 'data' => ['text' => 'Signs you need this repair', 'level' => 'h2']],
                ['type' => 'rich_text', 'data' => ['content' => '<p>'.fake()->paragraph(4).'</p>']],
            ],
            'icon' => 'screen',
            'is_active' => true,
            'sort' => fake()->numberBetween(1, 100),
        ];
    }

    /**
     * Attach repair types this page covers.
     */
    public function covering(RepairType ...$repairTypes): static
    {
        return $this->afterCreating(function (Service $service) use ($repairTypes): void {
            foreach ($repairTypes as $index => $repairType) {
                $service->repairTypes()->attach($repairType, ['sort' => $index]);
            }
        });
    }
}
