<?php

use App\Enums\PublishStatus;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Service;
use App\Models\Tag;
use App\Settings\SiteSettings;
use Illuminate\Support\Carbon;

afterEach(fn () => Carbon::setTestNow());

it('lists live posts only, newest first', function () {
    $older = Post::factory()->create(['title' => 'Older guide', 'published_at' => now()->subDays(10)]);
    $newer = Post::factory()->create(['title' => 'Newer guide', 'published_at' => now()->subDay()]);
    Post::factory()->draft()->create(['title' => 'Draft guide']);
    Post::factory()->scheduled()->create(['title' => 'Future guide']);
    Post::factory()->create(['title' => 'In review guide', 'status' => PublishStatus::Review]);

    $this->get(route('blog.index'))
        ->assertOk()
        ->assertSeeInOrder([$newer->title, $older->title])
        ->assertDontSee('Draft guide')
        ->assertDontSee('Future guide')
        ->assertDontSee('In review guide');

    $this->get(route('blog.show', Post::query()->where('title', 'Draft guide')->value('slug')))->assertNotFound();
});

it('publishes a scheduled post when its time comes', function () {
    $post = Post::factory()->scheduled(now()->addHour())->create();

    $this->get($post->url())->assertNotFound();

    Carbon::setTestNow(now()->addHours(2));

    $this->get($post->url())->assertOk()->assertSee($post->title);
});

it('shows a post with its table of contents, reading time, author box and related posts', function () {
    $category = PostCategory::factory()->create(['name' => 'Battery Health']);
    $post = Post::factory()->for($category, 'category')->create([
        'blocks' => [
            ['type' => 'heading', 'data' => ['text' => 'Why batteries age', 'level' => 'h2']],
            ['type' => 'rich_text', 'data' => ['content' => '<p>'.str_repeat('Battery words here. ', 150).'</p>']],
            ['type' => 'heading', 'data' => ['text' => 'When to replace it', 'level' => 'h2']],
            ['type' => 'rich_text', 'data' => ['content' => '<p>Soon.</p>']],
        ],
    ]);
    $related = Post::factory()->for($category, 'category')->create(['title' => 'Battery tips that work']);

    expect($post->reading_minutes)->toBe(3);

    $this->get($post->url())
        ->assertOk()
        ->assertSee('On this page')
        ->assertSee('href="#why-batteries-age"', false)
        ->assertSee('id="when-to-replace-it"', false)
        ->assertSee('3 min read')
        ->assertSee($post->author->name)
        ->assertSee('Battery tips that work')
        ->assertSee('"@type":"BlogPosting"', false)
        ->assertSee('<meta property="og:type" content="article">', false);

    expect($related)->not->toBeNull();
});

it('links a post to its repair page', function () {
    $service = Service::factory()->create(['name' => 'Battery Replacement']);
    $post = Post::factory()->create(['service_id' => $service->id]);

    $this->get($post->url())->assertSee('See prices and book')->assertSee($service->url(), false);
});

it('shows category pages under the blog address, before posts', function () {
    $category = PostCategory::factory()->create(['name' => 'Water Damage', 'slug' => 'water-damage']);
    $post = Post::factory()->for($category, 'category')->create();

    $this->get('/blog/water-damage')
        ->assertOk()
        ->assertSee('Water Damage')
        ->assertSee($post->title);
});

it('keeps tag pages out of search engines unless switched on', function () {
    config(['zr.indexable_environments' => ['testing']]);
    $site = app(SiteSettings::class);
    $site->allow_indexing = true;
    $site->save();

    $tag = Tag::factory()->create(['name' => 'charging']);
    Post::factory()->create()->tags()->attach($tag);

    $this->get($tag->url())->assertOk()->assertSee('noindex, follow', false);

    $site->index_tag_pages = true;
    $site->save();

    $this->get($tag->url())->assertSee('content="index, follow', false);
});

it('publishes an RSS feed of live posts', function () {
    $post = Post::factory()->create(['title' => 'Fresh guide']);
    Post::factory()->draft()->create(['title' => 'Hidden draft']);

    $this->get(route('blog.feed'))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/rss+xml; charset=UTF-8')
        ->assertSee('<title>Fresh guide</title>', false)
        ->assertSee($post->url(), false)
        ->assertDontSee('Hidden draft');
});

it('records when live content is updated', function () {
    $post = Post::factory()->create(['published_at' => now()->subWeek()]);

    expect($post->content_updated_at)->toBeNull();

    $post->update(['title' => 'A better title']);

    expect($post->fresh()->content_updated_at)->not->toBeNull();
});

it('shows an author page with Person structured data', function () {
    $post = Post::factory()->create();

    $this->get($post->author->url())
        ->assertOk()
        ->assertSee($post->author->name)
        ->assertSee($post->title)
        ->assertSee('"@type":"Person"', false);
});

it('marks due scheduled posts as published', function () {
    $due = Post::factory()->scheduled(now()->addMinute())->create();
    $later = Post::factory()->scheduled(now()->addWeek())->create();

    Carbon::setTestNow(now()->addMinutes(5));

    $this->artisan('content:publish-scheduled')->assertSuccessful();

    expect($due->fresh()->status)->toBe(PublishStatus::Published)
        ->and($later->fresh()->status)->toBe(PublishStatus::Scheduled);
});
