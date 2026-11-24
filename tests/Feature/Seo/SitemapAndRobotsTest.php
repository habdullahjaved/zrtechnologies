<?php

use App\Enums\PublishStatus;
use App\Models\DeviceModel;
use App\Models\ModelRepairPage;
use App\Models\Page;
use App\Models\Post;
use App\Models\RepairPrice;
use App\Models\RepairType;
use App\Models\Service;
use App\Models\ServiceArea;
use App\Models\Video;
use App\Settings\SiteSettings;

it('lists every sitemap in the sitemap index', function () {
    $response = $this->get('/sitemap.xml')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

    foreach (['pages', 'services', 'models', 'locations', 'blog', 'videos'] as $type) {
        $response->assertSee(route('sitemaps.show', $type), false);
    }

    $this->get('/sitemaps/unknown.xml')->assertNotFound();
});

it('includes only pages search engines may index', function () {
    Page::factory()->create(['slug' => 'careers']);
    Page::factory()->draft()->create(['slug' => 'draft-page']);
    Page::factory()->create(['slug' => 'hidden-page'])->seoMeta()->create(['noindex' => true]);
    Page::factory()->create(['slug' => 'not-in-sitemap', 'show_in_sitemap' => false]);

    $this->get('/sitemaps/pages.xml')
        ->assertSee(url('careers'), false)
        ->assertSee(route('home'), false)
        ->assertDontSee('draft-page')
        ->assertDontSee('hidden-page')
        ->assertDontSee('not-in-sitemap');
});

it('lists repair pages and priced models, but not models without prices', function () {
    $repair = RepairType::factory()->create();
    $priced = DeviceModel::factory()->create(['slug' => 'iphone-15']);
    RepairPrice::factory()->for($repair)->for($priced)->create();
    DeviceModel::factory()->create(['slug' => 'iphone-unpriced']);
    $service = Service::factory()->covering($repair)->create(['og_image' => 'images/og/og-screen-replacement.jpg']);

    $this->get('/sitemaps/services.xml')
        ->assertSee($service->url(), false)
        ->assertSee('<image:loc>'.asset('images/og/og-screen-replacement.jpg').'</image:loc>', false);

    $this->get('/sitemaps/models.xml')
        ->assertSee($priced->url(), false)
        ->assertDontSee('iphone-unpriced');
});

it('lists location and model × repair pages only once their content passes the checks', function () {
    $thin = ServiceArea::factory()->create(['slug' => 'thin-town', 'intro' => 'Short.']);
    $rich = ServiceArea::factory()->create(['slug' => 'rich-town', 'intro' => fake()->words(450, true)]);

    $repair = RepairType::factory()->create();
    $model = DeviceModel::factory()->create();
    RepairPrice::factory()->for($repair)->for($model)->create();
    ModelRepairPage::factory()->for($model)->for($repair)->create(['intro' => 'Too short to index.']);

    $this->get('/sitemaps/locations.xml')
        ->assertSee($rich->url(), false)
        ->assertDontSee($thin->url(), false);

    $this->get('/sitemaps/models.xml')->assertDontSee('/'.$repair->slug.'<', false);
});

it('lists live posts with images, and real videos with video details', function () {
    $live = Post::factory()->create(['featured_image' => 'posts/cover.jpg']);
    $draft = Post::factory()->draft()->create();
    $video = Video::factory()->youTube('abcdefghijk')->create();
    $placeholder = Video::factory()->create();

    $this->get('/sitemaps/blog.xml')
        ->assertSee($live->url(), false)
        ->assertSee(asset('storage/posts/cover.jpg'), false)
        ->assertSee($live->category->url(), false)
        ->assertSee($live->author->url(), false)
        ->assertDontSee($draft->slug);

    $this->get('/sitemaps/videos.xml')
        ->assertSee(route('videos.show', $video->slug), false)
        ->assertSee('<video:player_loc>https://www.youtube-nocookie.com/embed/abcdefghijk', false)
        ->assertSee('<video:duration>45</video:duration>', false)
        ->assertDontSee($placeholder->slug);
});

it('points robots.txt at the sitemap and adds the owner’s extra rules in production', function () {
    app()->detectEnvironment(fn () => 'production');
    $site = app(SiteSettings::class);
    $site->allow_indexing = true;
    $site->robots_extra = "Disallow: /*?sort=\n\nDisallow: /old-section";
    $site->save();

    $lines = explode("\n", $this->get('/robots.txt')->getContent());

    expect($lines)->toContain('Sitemap: '.route('sitemap'))
        ->toContain('Disallow: /*?sort=')
        ->toContain('Disallow: /old-section')
        ->toContain('Disallow: /blog/tag/');
});

it('keeps scheduled and draft content out of every sitemap', function () {
    $scheduled = Post::factory()->scheduled()->create();
    Page::factory()->create(['slug' => 'later', 'status' => PublishStatus::Scheduled, 'published_at' => now()->addWeek()]);

    $this->get('/sitemaps/blog.xml')->assertDontSee($scheduled->slug);
    $this->get('/sitemaps/pages.xml')->assertDontSee(url('later'), false);
});
