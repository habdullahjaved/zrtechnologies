<?php

namespace App\Http\Controllers;

use App\Enums\SystemPage;
use App\Models\Faq;
use App\Models\Page;
use App\Services\Seo\SchemaGraph;
use App\Services\Seo\SeoResolver;
use App\Support\Content\BlockRenderer;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

/**
 * Pages built from content blocks: about, warranty, the legal pages and any
 * custom page created in the admin (served at /{slug}).
 */
class PageController extends Controller
{
    public function __construct(
        private readonly SeoResolver $seo,
        private readonly SchemaGraph $schema,
        private readonly BlockRenderer $renderer,
    ) {}

    public function system(string $key): View
    {
        $page = Page::query()->where('key', $key)->with(['seoMeta', 'faqs' => fn ($faqs) => $faqs->where('is_active', true)])->first();

        abort_if($page === null || ! $page->isLive(), 404);

        return $this->render($page);
    }

    public function show(Request $request): View
    {
        $slug = trim($request->path(), '/');

        abort_unless(preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug) === 1, 404);

        $page = Page::query()
            ->whereNull('key')
            ->where('slug', $slug)
            ->live()
            ->with(['seoMeta', 'faqs' => fn ($faqs) => $faqs->where('is_active', true)])
            ->firstOrFail();

        return $this->render($page);
    }

    private function render(Page $page): View
    {
        $content = $this->renderer->render($page->blocks);
        $breadcrumbs = [['label' => $page->title]];
        $canonical = $page->url();

        /** @var Collection<int, Faq> $faqs */
        $faqs = $page->faqs;

        $pageType = match ($page->key) {
            SystemPage::About => 'AboutPage',
            default => 'WebPage',
        };

        $seo = $this->seo->forPage($page, $breadcrumbs, pageType: $pageType)
            ->withSchema([$this->schema->faqNode($faqs, $canonical)]);

        return view('pages.page', [
            'seo' => $seo,
            'page' => $page,
            'content' => $content,
            'breadcrumbs' => $breadcrumbs,
            'faqs' => $faqs,
            'isLegal' => $page->key?->isLegal() ?? false,
        ]);
    }
}
