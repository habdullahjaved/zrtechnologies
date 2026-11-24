<?php

namespace Database\Factories;

use App\Models\Page;
use App\Models\SeoMeta;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SeoMeta>
 */
class SeoMetaFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'seoable_type' => (new Page)->getMorphClass(),
            'seoable_id' => Page::factory(),
            'title' => fake()->sentence(5),
            'description' => fake()->sentence(18),
            'noindex' => false,
            'nofollow' => false,
        ];
    }
}
