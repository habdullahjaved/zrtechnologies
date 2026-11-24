<?php

namespace Database\Seeders\Content;

/**
 * Builds content blocks in the same shape the admin's block builder saves.
 *
 * @phpstan-type Block array{type: string, data: array<string, mixed>}
 */
final class Blocks
{
    /**
     * @return Block
     */
    public static function h2(string $text): array
    {
        return ['type' => 'heading', 'data' => ['text' => $text, 'level' => 'h2']];
    }

    /**
     * @return Block
     */
    public static function h3(string $text): array
    {
        return ['type' => 'heading', 'data' => ['text' => $text, 'level' => 'h3']];
    }

    /**
     * Rich text (HTML from the editor's allowed tags).
     *
     * @return Block
     */
    public static function text(string $html): array
    {
        return ['type' => 'rich_text', 'data' => ['content' => $html]];
    }

    /**
     * @param  'tip'|'info'|'warning'  $tone
     * @return Block
     */
    public static function callout(string $tone, string $title, string $text): array
    {
        return ['type' => 'callout', 'data' => ['tone' => $tone, 'title' => $title, 'text' => $text]];
    }

    /**
     * @param  list<array{0: string, 1: string}>  $steps  [title, text]
     * @return Block
     */
    public static function steps(array $steps): array
    {
        return ['type' => 'steps', 'data' => ['items' => array_map(
            fn (array $step): array => ['title' => $step[0], 'text' => $step[1]],
            $steps,
        )]];
    }

    /**
     * @param  list<string>  $rows  Pipe-separated cells, e.g. "Feature | Glass | Film".
     * @return Block
     */
    public static function table(string $caption, string $header, array $rows): array
    {
        return ['type' => 'table', 'data' => [
            'caption' => $caption,
            'header' => $header,
            'rows' => array_map(fn (string $row): array => ['cells' => $row], $rows),
        ]];
    }

    /**
     * @return Block
     */
    public static function cta(?string $title = null, ?string $text = null, ?string $buttonLabel = null): array
    {
        return ['type' => 'cta', 'data' => ['title' => $title, 'text' => $text, 'button_label' => $buttonLabel, 'repair_type_id' => null, 'device_model_id' => null]];
    }
}
