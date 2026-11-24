<?php

namespace Database\Factories;

use App\Enums\VideoSource;
use App\Models\Video;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Video>
 */
class VideoFactory extends Factory
{
    /**
     * A placeholder ("Video coming soon") by default.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = rtrim(fake()->unique()->sentence(5), '.');

        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'description' => fake()->sentence(16),
            'source' => VideoSource::YouTube,
            'is_placeholder' => true,
            'placeholder_label' => $title,
            'is_active' => true,
            'show_on_home' => true,
            'sort' => fake()->numberBetween(1, 100),
        ];
    }

    public function youTube(string $id = 'dQw4w9WgXcQ'): static
    {
        return $this->state([
            'source' => VideoSource::YouTube,
            'external_url' => "https://www.youtube.com/shorts/{$id}",
            'duration_seconds' => 45,
            'is_placeholder' => false,
            'published_at' => now()->subDay(),
        ]);
    }
}
