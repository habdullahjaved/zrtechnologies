<?php

namespace App\Support\Content;

use Filament\Forms\Components\RichEditor\RichContentRenderer;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/**
 * Renders content blocks with their Blade partials (resources/views/components/blocks).
 *
 * Rich text is sanitised (no scripts, event handlers or unsafe URLs), and
 * every H2/H3 gets an id so the table of contents can link to it.
 */
final class BlockRenderer
{
    /** @var array<string, true> */
    private array $usedIds = [];

    /** @var list<array{id: string, text: string, level: int}> */
    private array $headings = [];

    /** @var list<int> */
    private array $faqIds = [];

    /**
     * @param  array<int|string, mixed>|null  $blocks
     */
    public function render(?array $blocks): RenderedContent
    {
        $this->usedIds = [];
        $this->headings = [];
        $this->faqIds = [];

        $html = '';

        foreach ($blocks ?? [] as $block) {
            if (! is_array($block) || ! is_string($block['type'] ?? null)) {
                continue;
            }

            $html .= $this->renderBlock($block['type'], is_array($block['data'] ?? null) ? $block['data'] : []);
        }

        return new RenderedContent(new HtmlString($html), $this->headings, array_values(array_unique($this->faqIds)));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function renderBlock(string $type, array $data): string
    {
        $view = 'components.blocks.'.str_replace('_', '-', $type);

        if (! view()->exists($view)) {
            return '';
        }

        if ($type === 'heading') {
            $level = ($data['level'] ?? 'h2') === 'h3' ? 3 : 2;
            $text = trim((string) ($data['text'] ?? ''));

            if ($text === '') {
                return '';
            }

            $data['id'] = $this->registerHeading($text, $level);
            $data['level'] = $level;
        }

        if ($type === 'rich_text') {
            $content = $data['content'] ?? '';
            $data['content'] = new HtmlString($this->prepareRichText(is_array($content) ? RichContentRenderer::make($content)->toUnsafeHtml() : (string) $content));
        }

        if ($type === 'faqs') {
            foreach ((array) ($data['faq_ids'] ?? []) as $id) {
                $this->faqIds[] = (int) $id;
            }
        }

        return view($view, ['data' => $data])->render();
    }

    /**
     * Sanitise editor HTML and give its headings ids for the table of contents.
     */
    private function prepareRichText(string $html): string
    {
        $html = Str::sanitizeHtml($html);

        return (string) preg_replace_callback(
            '~<h([23])(\s[^>]*)?>(.*?)</h\1>~is',
            function (array $matches): string {
                $level = (int) $matches[1];
                $inner = $matches[3];
                $text = trim(html_entity_decode(strip_tags($inner), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

                if ($text === '') {
                    return $matches[0];
                }

                $id = $this->registerHeading($text, $level);

                return "<h{$level} id=\"{$id}\">{$inner}</h{$level}>";
            },
            $html,
        );
    }

    private function registerHeading(string $text, int $level): string
    {
        $base = Str::slug($text) ?: 'section';
        $id = $base;
        $suffix = 2;

        while (isset($this->usedIds[$id])) {
            $id = $base.'-'.$suffix++;
        }

        $this->usedIds[$id] = true;
        $this->headings[] = ['id' => $id, 'text' => $text, 'level' => $level];

        return $id;
    }
}
