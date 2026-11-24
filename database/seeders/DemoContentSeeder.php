<?php

namespace Database\Seeders;

use App\Enums\ReviewSource;
use App\Models\Review;
use App\Models\ServiceArea;
use Illuminate\Database\Seeder;

/**
 * Local development only: clearly labelled sample reviews so the review
 * layouts can be checked. They are flagged is_sample, shown with a "Sample"
 * badge, never shown when the site runs in production, and never seeded there
 * (UK DMCC Act 2024 bans fake reviews).
 */
class DemoContentSeeder extends Seeder
{
    /**
     * @var list<array{0: string, 1: int, 2: string, 3: string}> name, rating, area slug, text
     */
    private const array SAMPLE_REVIEWS = [
        ['Sample Customer A', 5, 'blackburn', 'Sample review for layout testing. Booked in the morning, screen replaced and phone back the same afternoon.'],
        ['Sample Customer B', 5, 'darwen', 'Sample review for layout testing. Battery replaced while I was at work, really easy.'],
        ['Sample Customer C', 4, 'accrington', 'Sample review for layout testing. Charging port cleaned, no new part needed.'],
        ['Sample Customer D', 5, 'clitheroe', 'Sample review for layout testing. Collected from home and kept up to date throughout.'],
        ['Sample Customer E', 5, 'great-harwood', 'Sample review for layout testing. Clear price before booking and no surprises.'],
        ['Sample Customer F', 5, 'blackburn', 'Sample review for layout testing. Back glass replaced, looks brand new.'],
    ];

    public function run(): void
    {
        if (app()->isProduction() || Review::query()->where('is_sample', true)->exists()) {
            return;
        }

        $areas = ServiceArea::query()->pluck('id', 'slug');

        foreach (self::SAMPLE_REVIEWS as $index => [$name, $rating, $area, $body]) {
            Review::query()->create([
                'source' => ReviewSource::Website,
                'reviewer_name' => $name,
                'rating' => $rating,
                'body' => $body,
                'reviewed_at' => now()->subDays(($index + 1) * 9),
                'service_area_id' => $areas[$area] ?? null,
                'is_verified' => false,
                'is_published' => true,
                'is_sample' => true,
            ]);
        }
    }
}
