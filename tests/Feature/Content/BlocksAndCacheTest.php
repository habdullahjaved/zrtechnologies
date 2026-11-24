<?php

use App\Enums\MenuLocation;
use App\Enums\SystemPage;
use App\Models\Faq;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\User;
use App\Services\Cache\PageCache;
use App\Services\Seo\SeoResolver;
use App\Support\Content\BlockRenderer;
use App\Support\Content\Blocks;

it('sanitises rich text and gives headings ids for the table of contents', function () {
    $rendered = app(BlockRenderer::class)->render([
        ['type' => 'heading', 'data' => ['text' => 'Before you book', 'level' => 'h2']],
        ['type' => 'rich_text', 'data' => ['content' => '<h2>Before you book</h2><p onclick="steal()">Hi<script>alert(1)</script></p><a href="javascript:alert(1)">bad</a>']],
        ['type' => 'heading', 'data' => ['text' => 'Details', 'level' => 'h3']],
        ['type' => 'unknown_block', 'data' => []],
    ]);

    $html = $rendered->html->toHtml();

    expect($html)->not->toContain('<script')
        ->not->toContain('onclick')
        ->not->toContain('javascript:')
        ->toContain('id="before-you-book"')
        ->toContain('id="before-you-book-2"')
        ->and(array_column($rendered->tableOfContents(), 'id'))->toBe(['before-you-book', 'before-you-book-2'])
        ->and($rendered->headings)->toHaveCount(3);
});

it('renders every block type', function () {
    $faq = Faq::factory()->create(['question' => 'Do you need my passcode?']);

    $html = app(BlockRenderer::class)->render([
        ['type' => 'callout', 'data' => ['tone' => 'warning', 'title' => 'Switch it off', 'text' => 'Do not charge it.']],
        ['type' => 'table', 'data' => ['caption' => 'Compared', 'header' => 'Feature | Glass | Film', 'rows' => [['cells' => 'Drops | Good | Poor']]]],
        ['type' => 'steps', 'data' => ['items' => [['title' => 'Book online', 'text' => 'Two minutes.']]]],
        ['type' => 'faqs', 'data' => ['faq_ids' => [(string) $faq->id]]],
        ['type' => 'cta', 'data' => ['title' => 'Need it fixed?']],
    ]);

    expect($html->html->toHtml())
        ->toContain('Switch it off')
        ->toContain('<th scope="col" class="px-4 py-3 font-semibold">Glass</th>')
        ->toContain('Book online')
        ->toContain('Do you need my passcode?')
        ->toContain('Need it fixed?')
        ->and($html->faqIds)->toBe([$faq->id]);
});

it('counts words and builds plain text from blocks', function () {
    $text = Blocks::plainText([
        ['type' => 'heading', 'data' => ['text' => 'Three word heading']],
        ['type' => 'rich_text', 'data' => ['content' => '<p>Four <strong>words</strong> right here</p>']],
    ]);

    expect($text)->toBe('Three word heading Four words right here')
        ->and(Blocks::wordCount($text))->toBe(7);
});

it('fills title patterns and drops segments with empty placeholders', function () {
    $seo = app(SeoResolver::class);

    expect($seo->pattern('{repair} for {model} | From {price}', ['repair' => 'Battery', 'model' => 'iPhone 15', 'price' => '£49']))
        ->toBe('Battery for iPhone 15 | From £49')
        ->and($seo->pattern('{repair} for {model} | From {price}', ['repair' => 'Battery', 'model' => 'iPhone 15', 'price' => null]))
        ->toBe('Battery for iPhone 15');
});

it('serves cached pages to anonymous visitors with their own CSRF token', function () {
    config(['zr.page_cache.enabled' => true]);

    $first = $this->get(route('about'))->assertNotFound();
    Page::factory()->system(SystemPage::About)->create(['intro' => 'Original intro.']);

    $this->get(route('about'))->assertOk()->assertHeader('X-Page-Cache', 'MISS');

    $cached = $this->get(route('about'))->assertOk()->assertHeader('X-Page-Cache', 'HIT')->assertSee('Original intro.');
    expect($cached->getContent())->toContain(csrf_token())->not->toContain('__ZR_CSRF_TOKEN__');

    // Tracking parameters share the cached copy; other query strings never do.
    $this->get(route('about').'?utm_source=google&gclid=abc')->assertHeader('X-Page-Cache', 'HIT');
    $this->get(route('about').'?page=2')->assertHeaderMissing('X-Page-Cache');

    expect($first->status())->toBe(404);
});

it('clears the page cache when content is saved', function () {
    config(['zr.page_cache.enabled' => true]);
    $page = Page::factory()->system(SystemPage::About)->create(['intro' => 'Original intro.']);

    $this->get(route('about'))->assertSee('Original intro.');
    $this->get(route('about'))->assertHeader('X-Page-Cache', 'HIT');

    $page->update(['intro' => 'Updated intro.']);

    $this->get(route('about'))->assertHeader('X-Page-Cache', 'MISS')->assertSee('Updated intro.');
});

it('never caches pages for signed-in staff', function () {
    config(['zr.page_cache.enabled' => true]);
    Page::factory()->system(SystemPage::About)->create();

    $this->actingAs(User::factory()->create())->get(route('about'))->assertOk()->assertHeaderMissing('X-Page-Cache');

    expect(app(PageCache::class)->isEnabled())->toBeTrue();
});

it('builds the header and footer menus from the admin, falling back to the defaults', function () {
    $this->get(route('home'))->assertSee('href="'.route('contact').'"', false);

    MenuItem::factory()->create(['menu' => MenuLocation::Header, 'label' => 'Special offers', 'url' => '/offers']);

    $this->get(route('home'))
        ->assertSee('Special offers')
        ->assertSee('href="'.url('/offers').'"', false);
});
