<?php

use App\Models\DeviceModel;
use App\Models\NotFoundLog;
use App\Models\Page;
use App\Models\Post;
use App\Models\Redirect;
use App\Models\RepairPrice;

it('follows a redirect and counts the hit, keeping the query string', function () {
    $redirect = Redirect::factory()->create(['from_path' => '/old-prices', 'to_path' => '/repair-prices']);

    $this->get('/old-prices?utm_source=flyer')
        ->assertStatus(301)
        ->assertRedirect(url('/repair-prices').'?utm_source=flyer');

    expect($redirect->fresh()->hits)->toBe(1)
        ->and($redirect->fresh()->last_hit_at)->not->toBeNull();
});

it('supports temporary redirects to other websites, and ignores inactive ones', function () {
    Redirect::factory()->create(['from_path' => '/shop', 'to_path' => 'https://www.ebay.co.uk/str/zr', 'status_code' => 302]);
    Redirect::factory()->create(['from_path' => '/paused', 'to_path' => '/contact', 'is_active' => false]);

    $this->get('/shop')->assertStatus(302)->assertRedirect('https://www.ebay.co.uk/str/zr');
    $this->get('/paused')->assertNotFound();
});

it('matches old addresses whatever their case or trailing slash', function () {
    Redirect::factory()->create(['from_path' => '/Old-Page/', 'to_path' => '/contact']);

    expect(Redirect::query()->first()->from_path)->toBe('/old-page');

    $this->get('/old-page')->assertRedirect(url('/contact'));
});

it('logs missing pages with their hit count and referrer', function () {
    $this->get('/missing-page', ['referer' => 'https://www.google.com/'])->assertNotFound();
    $this->get('/missing-page')->assertNotFound();

    $log = NotFoundLog::query()->where('path', '/missing-page')->sole();

    expect($log->hits)->toBe(2)
        ->and($log->last_referrer)->toBe('https://www.google.com/');
});

it('does not log assets, scanners or the admin', function () {
    $this->get('/wp-login.php');
    $this->get('/build/assets/old.js');
    $this->get('/'.config('zr.admin.path').'/missing-thing');

    expect(NotFoundLog::query()->count())->toBe(0);
});

it('redirects the old address when a page’s slug changes', function () {
    $page = Page::factory()->create(['slug' => 'careers']);

    $page->update(['slug' => 'jobs']);

    $this->get('/careers')->assertStatus(301)->assertRedirect(url('/jobs'));
    expect(Redirect::query()->sole()->is_automatic)->toBeTrue();
});

it('never builds redirect chains or loops when slugs change repeatedly', function () {
    $model = DeviceModel::factory()->create(['slug' => 'iphone-17-air']);
    RepairPrice::factory()->for($model)->create();

    $model->update(['slug' => 'iphone-air']);
    $model->update(['slug' => 'iphone-air-2025']);
    $model->update(['slug' => 'iphone-17-air']);

    expect(Redirect::query()->where('from_path', '/iphone-repair/iphone-17-air')->exists())->toBeFalse()
        ->and(Redirect::query()->pluck('to_path')->unique()->all())->toBe(['/iphone-repair/iphone-17-air']);

    $this->get('/iphone-repair/iphone-air')->assertRedirect(url('/iphone-repair/iphone-17-air'));
    $this->get('/iphone-repair/iphone-17-air')->assertOk();
});

it('redirects blog posts when their slug changes', function () {
    $post = Post::factory()->create(['slug' => 'old-title']);

    $post->update(['slug' => 'new-title']);

    $this->get('/blog/old-title')->assertRedirect(url('/blog/new-title'));
});
