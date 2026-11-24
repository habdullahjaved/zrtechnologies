<?php

namespace App\Support\Content;

use Illuminate\Support\HtmlString;

/**
 * Content blocks rendered to HTML, with the headings for a table of contents
 * and any FAQs the blocks displayed (for FAQPage structured data).
 */
final readonly class RenderedContent
{
    /**
     * @param  list<array{id: string, text: string, level: int}>  $headings
     * @param  list<int>  $faqIds
     */
    public function __construct(
        public HtmlString $html,
        public array $headings = [],
        public array $faqIds = [],
    ) {}

    public function isEmpty(): bool
    {
        return trim($this->html->toHtml()) === '';
    }

    /**
     * Top-level (H2) headings, for the table of contents.
     *
     * @return list<array{id: string, text: string, level: int}>
     */
    public function tableOfContents(): array
    {
        return array_values(array_filter($this->headings, fn (array $heading): bool => $heading['level'] === 2));
    }
}
