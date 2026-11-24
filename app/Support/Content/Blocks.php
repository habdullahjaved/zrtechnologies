<?php

namespace App\Support\Content;

use Filament\Forms\Components\RichEditor\RichContentRenderer;

/**
 * Helpers for content blocks stored by the admin's block builder as
 * [['type' => 'rich_text', 'data' => [...]], ...].
 *
 * Block types: heading, rich_text, image, callout, table, steps, faqs, video,
 * cta, price_table. See App\Filament\Content\ContentBlocks for their fields.
 */
final class Blocks
{
    /**
     * The readable text of a set of blocks, used for word counts, reading time
     * and the unique-content checks. Shared content (FAQs, videos, prices) is
     * not counted, because it is not unique to the page.
     *
     * @param  array<int|string, mixed>|null  $blocks
     */
    public static function plainText(?array $blocks): string
    {
        $parts = [];

        foreach ($blocks ?? [] as $block) {
            if (! is_array($block)) {
                continue;
            }

            $data = is_array($block['data'] ?? null) ? $block['data'] : [];

            $parts[] = match ($block['type'] ?? null) {
                'heading' => (string) ($data['text'] ?? ''),
                'rich_text' => self::richText($data['content'] ?? null),
                'callout' => ($data['title'] ?? '').' '.($data['text'] ?? ''),
                'image' => (string) ($data['caption'] ?? ''),
                'table' => ($data['caption'] ?? '').' '.($data['header'] ?? '').' '.implode(' ', array_column(is_array($data['rows'] ?? null) ? $data['rows'] : [], 'cells')),
                'steps' => implode(' ', array_map(
                    fn (mixed $step): string => is_array($step) ? ($step['title'] ?? '').' '.($step['text'] ?? '') : '',
                    is_array($data['items'] ?? null) ? $data['items'] : [],
                )),
                'cta' => ($data['title'] ?? '').' '.($data['text'] ?? ''),
                default => '',
            };
        }

        return trim(preg_replace('/\s+/u', ' ', str_replace('|', ' ', implode(' ', $parts))) ?? '');
    }

    /**
     * Rich text is HTML once saved, but the editor's JSON document while a form is open.
     */
    public static function richText(mixed $content): string
    {
        if (is_array($content)) {
            return RichContentRenderer::make($content)->toText();
        }

        return self::htmlToText((string) $content);
    }

    public static function htmlToText(string $html): string
    {
        $text = strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>', '</li>', '</h2>', '</h3>'], ' ', $html));

        return html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    public static function wordCount(?string $text): int
    {
        $text = trim((string) $text);

        if ($text === '') {
            return 0;
        }

        return count(preg_split('/\s+/u', $text) ?: []);
    }

    /**
     * Split a pipe-separated table row ("Model | Price | Warranty") into cells.
     *
     * @return list<string>
     */
    public static function cells(?string $row): array
    {
        return array_map('trim', explode('|', (string) $row));
    }
}
