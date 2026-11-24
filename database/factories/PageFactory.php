<?php

namespace Database\Factories;

use App\Enums\PublishStatus;
use App\Enums\SystemPage;
use App\Models\Page;
use App\Support\Content\SystemPageDefaults;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Page>
 */
class PageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = Str::title(fake()->unique()->words(3, true));

        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'intro' => fake()->sentence(16),
            'blocks' => [
                ['type' => 'heading', 'data' => ['text' => 'About this page', 'level' => 'h2']],
                ['type' => 'rich_text', 'data' => ['content' => '<p>'.fake()->paragraph(5).'</p>']],
            ],
            'status' => PublishStatus::Published,
            'published_at' => now()->subDay(),
            'show_in_sitemap' => true,
        ];
    }

    public function draft(): static
    {
        return $this->state(['status' => PublishStatus::Draft, 'published_at' => null]);
    }

    /**
     * A system page with its default wording, e.g. Page::factory()->system(SystemPage::About).
     */
    public function system(SystemPage $key): static
    {
        $defaults = SystemPageDefaults::for($key);

        return $this->state([
            'key' => $key,
            'title' => $defaults['title'],
            'slug' => $key->slug(),
            'eyebrow' => $defaults['eyebrow'] ?? null,
            'intro' => $defaults['intro'] ?? null,
            'needs_legal_review' => $key->isLegal(),
        ]);
    }
}
