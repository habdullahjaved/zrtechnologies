<?php

namespace Database\Factories;

use App\Enums\FaqTopic;
use App\Models\Faq;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Faq>
 */
class FaqFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'topic' => fake()->randomElement(FaqTopic::cases()),
            'question' => rtrim(fake()->unique()->sentence(8), '.').'?',
            'answer' => fake()->paragraph(3),
            'show_on_faq_page' => true,
            'is_active' => true,
            'sort' => fake()->numberBetween(1, 100),
        ];
    }

    public function topic(FaqTopic $topic): static
    {
        return $this->state(['topic' => $topic]);
    }
}
