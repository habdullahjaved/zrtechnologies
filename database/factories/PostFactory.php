<?php

namespace Database\Factories;

use App\Enums\PublishStatus;
use App\Models\Author;
use App\Models\Post;
use App\Models\PostCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = rtrim(fake()->unique()->sentence(6), '.');

        return [
            'post_category_id' => PostCategory::factory(),
            'author_id' => Author::factory(),
            'title' => $title,
            'slug' => Str::slug($title),
            'excerpt' => fake()->sentence(20),
            'blocks' => [
                ['type' => 'heading', 'data' => ['text' => 'What you need to know', 'level' => 'h2']],
                ['type' => 'rich_text', 'data' => ['content' => '<p>'.fake()->paragraphs(3, true).'</p>']],
                ['type' => 'heading', 'data' => ['text' => 'What to do next', 'level' => 'h2']],
                ['type' => 'rich_text', 'data' => ['content' => '<p>'.fake()->paragraph(4).'</p>']],
            ],
            'status' => PublishStatus::Published,
            'published_at' => now()->subDays(fake()->numberBetween(1, 60)),
        ];
    }

    public function draft(): static
    {
        return $this->state(['status' => PublishStatus::Draft, 'published_at' => null]);
    }

    public function scheduled(?\DateTimeInterface $at = null): static
    {
        return $this->state(['status' => PublishStatus::Scheduled, 'published_at' => $at ?? now()->addWeek()]);
    }
}
