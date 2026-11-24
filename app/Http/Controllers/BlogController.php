<?php

namespace App\Http\Controllers;

use App\Enums\SystemPage;
use App\Models\Author;
use App\Models\Faq;
use App\Models\Page;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Tag;
use App\Services\Seo\SchemaGraph;
use App\Services\Seo\SeoResolver;
use App\Settings\SiteSettings;
use App\Support\Content\BlockRenderer;
use App\Support\PublicFile;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Response;

/**
 * The blog: /blog, /blog/{category}, /blog/{post}, author bio pages, tag
 * pages (noindex unless switched on) and the RSS feed.
 *
 * /blog/{slug} checks categories before posts; the admin stops a post and a
 * category from sharing a slug.
 */
class BlogController extends Controller
{
    private const int PER_PAGE = 9;

    public function __construct(
        private readonly SeoResolver $seo,
        private readonly SchemaGraph $schema,
        private readonly SiteSettings $site,
    ) {}

    public function index(): View
    {
        $page = Page::system(SystemPage::Blog);
        $posts = $this->livePosts()->paginate(self::PER_PAGE);
        $breadcrumbs = [['label' => 'Blog']];

        return view('pages.blog.index', [
            'seo' => $this->seo->make(
                meta: $page->seoMeta,
                title: $page->title,
                description: $page->intro,
                image: 'images/og/og-blog.jpg',
                canonical: $this->paginatedCanonical($posts, route('blog.index')),
                breadcrumbs: $breadcrumbs,
                pageType: 'CollectionPage',
            ),
            'page' => $page,
            'heading' => $page->title,
            'intro' => $page->intro,
            'posts' => $posts,
            'categories' => $this->categories(),
            'breadcrumbs' => $breadcrumbs,
            'activeCategory' => null,
        ]);
    }

    public function show(string $slug, BlockRenderer $renderer): View
    {
        $category = PostCategory::query()->where('slug', $slug)->with('seoMeta')->first();

        if ($category !== null) {
            return $this->category($category);
        }

        $post = Post::query()->live()->where('slug', $slug)
            ->with(['seoMeta', 'category', 'author', 'tags', 'service', 'faqs' => fn ($faqs) => $faqs->where('is_active', true)])
            ->firstOrFail();

        return $this->post($post, $renderer);
    }

    public function author(string $slug): View
    {
        $author = Author::query()->where('slug', $slug)->where('is_active', true)->firstOrFail();
        $posts = $this->livePosts()->where('author_id', $author->getKey())->paginate(self::PER_PAGE);
        $canonical = $author->url();
        $breadcrumbs = [
            ['label' => 'Blog', 'url' => route('blog.index')],
            ['label' => $author->name],
        ];

        return view('pages.blog.author', [
            'seo' => $this->seo->make(
                meta: null,
                title: "{$author->name}, {$author->role_title}",
                description: $author->bio,
                canonical: $this->paginatedCanonical($posts, $canonical),
                breadcrumbs: $breadcrumbs,
                schema: [$this->personNode($author)],
                type: 'profile',
                pageType: 'ProfilePage',
            ),
            'author' => $author,
            'posts' => $posts,
            'breadcrumbs' => $breadcrumbs,
        ]);
    }

    public function tag(string $slug): View
    {
        $tag = Tag::query()->where('slug', $slug)->firstOrFail();
        $posts = $this->livePosts()->whereHas('tags', fn (Builder $tags) => $tags->whereKey($tag->getKey()))->paginate(self::PER_PAGE);
        $breadcrumbs = [
            ['label' => 'Blog', 'url' => route('blog.index')],
            ['label' => "Tagged “{$tag->name}”"],
        ];

        return view('pages.blog.index', [
            'seo' => $this->seo->make(
                meta: null,
                title: "{$tag->name}: iPhone guides",
                description: "Guides and advice about {$tag->name} from the ZR Technologies repair team.",
                noindex: ! $this->site->index_tag_pages,
                canonical: $this->paginatedCanonical($posts, $tag->url()),
                breadcrumbs: $breadcrumbs,
                pageType: 'CollectionPage',
            ),
            'page' => null,
            'heading' => "Guides tagged “{$tag->name}”",
            'intro' => null,
            'posts' => $posts,
            'categories' => $this->categories(),
            'breadcrumbs' => $breadcrumbs,
            'activeCategory' => null,
        ]);
    }

    public function feed(): Response
    {
        $posts = $this->livePosts()->limit(20)->get();

        return response()
            ->view('feeds.blog', [
                'posts' => $posts,
                'page' => Page::system(SystemPage::Blog),
                'updatedAt' => $posts->first()?->lastUpdatedAt() ?? now(),
            ])
            ->header('Content-Type', 'application/rss+xml; charset=UTF-8');
    }

    private function category(PostCategory $category): View
    {
        $posts = $this->livePosts()->where('post_category_id', $category->getKey())->paginate(self::PER_PAGE);
        $breadcrumbs = [
            ['label' => 'Blog', 'url' => route('blog.index')],
            ['label' => $category->name],
        ];

        return view('pages.blog.index', [
            'seo' => $this->seo->make(
                meta: $category->seoMeta,
                title: $this->seo->pattern($this->site->title_pattern_post_category, ['name' => $category->name]),
                description: $category->description,
                image: 'images/og/og-blog.jpg',
                canonical: $this->paginatedCanonical($posts, $category->url()),
                breadcrumbs: $breadcrumbs,
                pageType: 'CollectionPage',
            ),
            'page' => null,
            'heading' => $category->name,
            'intro' => $category->description,
            'posts' => $posts,
            'categories' => $this->categories(),
            'breadcrumbs' => $breadcrumbs,
            'activeCategory' => $category,
        ]);
    }

    private function post(Post $post, BlockRenderer $renderer): View
    {
        $content = $renderer->render($post->blocks);
        $canonical = $post->url();
        $breadcrumbs = [
            ['label' => 'Blog', 'url' => route('blog.index')],
            ['label' => $post->category->name, 'url' => $post->category->url()],
            ['label' => $post->title],
        ];

        /** @var Collection<int, Faq> $faqs */
        $faqs = $post->faqs;

        // FAQ blocks inside the post count too, as long as they are shown.
        if ($content->faqIds !== []) {
            $faqs = $faqs->merge(Faq::query()->listed()->whereKey($content->faqIds)->get())->unique('id')->values();
        }

        $image = $post->featured_image ? PublicFile::publicPath($post->featured_image) : 'images/og/og-blog.jpg';

        $seo = $this->seo->make(
            meta: $post->seoMeta,
            title: $this->seo->pattern($this->site->title_pattern_post, ['title' => $post->title]),
            description: $post->excerpt,
            image: $image,
            imageAlt: $post->featured_image_alt ?? $post->title,
            canonical: $canonical,
            breadcrumbs: $breadcrumbs,
            schema: [
                $this->articleNode($post, $canonical, PublicFile::url($image) ?? asset('images/og/og-blog.jpg')),
                $post->author ? $this->personNode($post->author) : null,
                $this->schema->faqNode($faqs, $canonical),
            ],
            type: 'article',
            publishedTime: $post->published_at?->toIso8601String(),
            modifiedTime: $post->lastUpdatedAt()?->toIso8601String(),
        );

        return view('pages.blog.show', [
            'seo' => $seo,
            'post' => $post,
            'content' => $content,
            'faqs' => $post->faqs,
            'breadcrumbs' => $breadcrumbs,
            'related' => $this->relatedPosts($post),
            'shareUrl' => $canonical,
        ]);
    }

    /**
     * Hand-picked related posts first, topped up with the latest in the same category.
     *
     * @return Collection<int, Post>
     */
    private function relatedPosts(Post $post): Collection
    {
        $picked = $post->relatedPosts()->live()->with('category')->limit(3)->get();

        if ($picked->count() >= 3) {
            return $picked;
        }

        $more = $this->livePosts()
            ->whereKeyNot([$post->getKey(), ...$picked->modelKeys()])
            ->where('post_category_id', $post->post_category_id)
            ->limit(3 - $picked->count())
            ->get();

        return $picked->concat($more)->values();
    }

    /**
     * @return Builder<Post>
     */
    private function livePosts(): Builder
    {
        return Post::query()->live()->latestFirst()->with(['category', 'author']);
    }

    /**
     * Categories that have at least one live post.
     *
     * @return Collection<int, PostCategory>
     */
    private function categories(): Collection
    {
        return PostCategory::query()->ordered()->whereHas('posts', fn (Builder $posts) => $posts->live())->get();
    }

    /**
     * Each page of a list is its own canonical URL (page 1 has no ?page=).
     *
     * @param  LengthAwarePaginator<int, Post>  $posts
     */
    private function paginatedCanonical(LengthAwarePaginator $posts, string $base): string
    {
        return $posts->currentPage() > 1 ? $base.'?page='.$posts->currentPage() : $base;
    }

    /**
     * @return array<string, mixed>
     */
    private function articleNode(Post $post, string $canonical, string $image): array
    {
        return array_filter([
            '@type' => 'BlogPosting',
            '@id' => $canonical.'#article',
            'mainEntityOfPage' => ['@id' => $canonical.'#webpage'],
            'headline' => $post->title,
            'description' => $post->excerpt,
            'image' => [$image],
            'datePublished' => $post->published_at?->toIso8601String(),
            'dateModified' => $post->lastUpdatedAt()?->toIso8601String(),
            'wordCount' => $post->word_count,
            'articleSection' => $post->category->name,
            'keywords' => $post->tags->pluck('name')->implode(', ') ?: null,
            'inLanguage' => 'en-GB',
            'author' => $post->author
                ? ['@id' => $post->author->url().'#person']
                : ['@id' => $this->schema->businessId()],
            'publisher' => ['@id' => $this->schema->businessId()],
        ], fn (mixed $value): bool => $value !== null);
    }

    /**
     * @return array<string, mixed>
     */
    private function personNode(Author $author): array
    {
        $node = [
            '@type' => 'Person',
            '@id' => $author->url().'#person',
            'name' => $author->name,
            'jobTitle' => $author->role_title,
            'description' => $author->bio,
            'url' => $author->url(),
            'worksFor' => ['@id' => $this->schema->businessId()],
        ];

        if (($photo = $author->photoUrl()) !== null) {
            $node['image'] = $photo;
        }

        if (($profiles = $author->profileUrls()) !== []) {
            $node['sameAs'] = $profiles;
        }

        return array_filter($node, fn (mixed $value): bool => $value !== null);
    }
}
