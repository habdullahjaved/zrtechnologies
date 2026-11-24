<?php

namespace App\Services\Content;

use App\Models\DeviceModel;
use App\Support\Content\Blocks;
use Illuminate\Support\Str;

/**
 * Suggests links from a blog post to repair pages and model pages it mentions
 * but does not link to yet. Shown in the post editor; nothing is added
 * automatically.
 */
class InternalLinkSuggester
{
    private const int LIMIT = 8;

    public function __construct(private readonly ServiceDirectory $directory) {}

    /**
     * @param  array<int|string, mixed>|null  $blocks
     * @return list<array{phrase: string, label: string, url: string}>
     */
    public function suggest(?array $blocks): array
    {
        $text = mb_strtolower(Blocks::plainText($blocks));

        if ($text === '') {
            return [];
        }

        $html = (string) json_encode($blocks);
        $suggestions = [];

        foreach ($this->targets() as $target) {
            if (count($suggestions) >= self::LIMIT) {
                break;
            }

            $path = (string) parse_url($target['url'], PHP_URL_PATH);

            if (str_contains($html, str_replace('/', '\/', $path))) {
                continue;
            }

            foreach ($target['phrases'] as $phrase) {
                if (preg_match('/\b'.preg_quote(mb_strtolower($phrase), '/').'\b/u', $text) === 1) {
                    $suggestions[] = ['phrase' => $phrase, 'label' => $target['label'], 'url' => $target['url']];

                    break;
                }
            }
        }

        return $suggestions;
    }

    /**
     * Repair pages first (the pillar pages posts should support), then models,
     * longest names first so "iPhone 15 Pro Max" wins over "iPhone 15".
     *
     * @return list<array{label: string, url: string, phrases: list<string>}>
     */
    private function targets(): array
    {
        $targets = [];

        foreach ($this->directory->services() as $service) {
            $phrases = [Str::lower($service->name)];

            foreach ($service->repairTypes as $repair) {
                $phrases[] = Str::lower($repair->name);
            }

            $targets[] = ['label' => 'iPhone '.$service->name, 'url' => $service->url(), 'phrases' => array_values(array_unique($phrases))];
        }

        $models = DeviceModel::query()->listed()->get(['id', 'name', 'slug'])
            ->sortByDesc(fn (DeviceModel $model): int => mb_strlen($model->name));

        foreach ($models as $model) {
            $targets[] = ['label' => $model->name.' repair', 'url' => $model->url(), 'phrases' => [$model->name]];
        }

        return $targets;
    }
}
