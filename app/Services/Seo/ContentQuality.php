<?php

namespace App\Services\Seo;

use App\Models\ModelRepairPage;
use App\Models\ServiceArea;
use App\Settings\SiteSettings;
use App\Support\Content\Blocks;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Thin-content protection for programmatic pages (locations and model × repair
 * pages). A page may be indexed only when its own, admin-written content is
 * long enough and not too similar to any sibling page.
 *
 * Similarity is the overlap of three-word phrases between two pages (Jaccard
 * index, as a percentage), which catches copy-and-paste pages with the town
 * name swapped.
 */
class ContentQuality
{
    private const int SHINGLE_SIZE = 3;

    public function __construct(private readonly SiteSettings $site) {}

    public function passes(int $uniqueWords, float $similarityPercent, int $minimumWords): bool
    {
        return $uniqueWords >= $minimumWords && $similarityPercent <= $this->site->max_similarity_percent;
    }

    /**
     * Recalculate word counts and similarity for every location page.
     */
    public function refreshServiceAreas(): void
    {
        $this->refresh(
            ServiceArea::query()->get(),
            fn (ServiceArea $area): string => implode(' ', [$area->summary, $area->intro, Blocks::plainText($area->blocks), $area->landmarks]),
        );
    }

    /**
     * Recalculate word counts and similarity for every model × repair page.
     */
    public function refreshModelRepairPages(): void
    {
        $this->refresh(
            ModelRepairPage::query()->get(),
            fn (ModelRepairPage $page): string => $page->intro.' '.Blocks::plainText($page->blocks),
        );
    }

    /**
     * Percentage of shared three-word phrases between two texts (0–100).
     */
    public static function similarity(string $first, string $second): float
    {
        $a = self::shingles($first);
        $b = self::shingles($second);

        if ($a === [] || $b === []) {
            return 0.0;
        }

        $intersection = count(array_intersect_key($a, $b));
        $union = count($a + $b);

        return round($intersection / $union * 100, 2);
    }

    /**
     * @template TModel of Model
     *
     * @param  Collection<int, TModel>  $records
     * @param  callable(TModel): string  $text
     */
    private function refresh(Collection $records, callable $text): void
    {
        $texts = $records->mapWithKeys(fn (Model $record): array => [$record->getKey() => $text($record)]);

        foreach ($records as $record) {
            $own = (string) $texts[$record->getKey()];
            $highest = 0.0;

            foreach ($texts as $id => $other) {
                if ($id !== $record->getKey()) {
                    $highest = max($highest, self::similarity($own, (string) $other));
                }
            }

            // A query update, so model events (and this refresh) are not triggered again.
            $record->newQuery()->whereKey($record->getKey())->update([
                'unique_word_count' => Blocks::wordCount($own),
                'similarity_score' => $highest,
            ]);
        }
    }

    /**
     * @return array<string, true>
     */
    private static function shingles(string $text): array
    {
        $words = preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $shingles = [];

        for ($i = 0, $last = count($words) - self::SHINGLE_SIZE; $i <= $last; $i++) {
            $shingles[implode(' ', array_slice($words, $i, self::SHINGLE_SIZE))] = true;
        }

        return $shingles;
    }
}
