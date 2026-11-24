<?php

namespace Database\Factories;

use App\Models\Lead;
use App\Models\Note;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Note>
 */
class NoteFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'notable_type' => (new Lead)->getMorphClass(),
            'notable_id' => Lead::factory(),
            'body' => fake()->sentence(),
            'is_pinned' => false,
        ];
    }
}
