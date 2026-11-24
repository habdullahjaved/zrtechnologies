<?php

use App\Models\Review;
use App\Models\Service;
use App\Models\Video;
use App\Settings\SocialSettings;

it('shows genuine reviews with a first-name-and-initial display name', function () {
    Review::factory()->create(['reviewer_name' => 'Sarah Khan', 'body' => 'Screen fixed the same afternoon.']);

    $this->get(route('reviews'))
        ->assertOk()
        ->assertSee('Sarah K.')
        ->assertDontSee('Sarah Khan')
        ->assertSee('Screen fixed the same afternoon.');
});

it('never shows sample reviews in production', function () {
    Review::factory()->sample()->create(['body' => 'Sample review for layout testing.']);
    Review::factory()->create(['body' => 'A real customer review.']);

    $this->get(route('reviews'))->assertSee('Sample review for layout testing.');

    app()->detectEnvironment(fn () => 'production');

    $this->get(route('reviews'))
        ->assertDontSee('Sample review for layout testing.')
        ->assertSee('A real customer review.');

    $this->get(route('home'))->assertDontSee('Sample review for layout testing.');
});

it('hides unpublished reviews and invites the first ones when there are none', function () {
    Review::factory()->create(['body' => 'Not shown yet.', 'is_published' => false]);

    $this->get(route('reviews'))
        ->assertDontSee('Not shown yet.')
        ->assertSee('Our first reviews are on their way');
});

it('sends /reviews/leave to the Google review form once it is set', function () {
    $this->get(route('reviews.leave'))->assertRedirect(route('contact'));

    $social = app(SocialSettings::class);
    $social->google_review_url = 'https://g.page/r/abc123/review';
    $social->save();

    $this->get(route('reviews.leave'))->assertRedirect('https://g.page/r/abc123/review');
});

it('shows placeholder videos as "coming soon" cards without a page of their own', function () {
    $placeholder = Video::factory()->create(['title' => 'Battery replacement', 'placeholder_label' => 'Battery replacement in 60 seconds']);

    $this->get(route('videos.index'))
        ->assertOk()
        ->assertSee('Video coming soon')
        ->assertSee('Battery replacement in 60 seconds');

    $this->get(route('videos.show', $placeholder->slug))->assertNotFound();
});

it('plays YouTube videos through a click-to-load facade with VideoObject data', function () {
    $service = Service::factory()->create(['name' => 'Screen Replacement']);
    $video = Video::factory()->youTube('abcdefghijk')->create(['title' => 'Screen repair in 60 seconds', 'service_id' => $service->id, 'transcript' => 'First we remove the screen.']);

    $html = $this->get(route('videos.show', $video->slug))
        ->assertOk()
        ->assertSee('Play video: Screen repair in 60 seconds')
        ->assertSee('https://i.ytimg.com/vi/abcdefghijk/hqdefault.jpg', false)
        ->assertSee('First we remove the screen.')
        ->assertSee('"@type":"VideoObject"', false)
        ->assertSee('"duration":"PT45S"', false)
        ->getContent();

    // The YouTube player itself only loads after the visitor presses play.
    expect(substr_count($html, '<iframe'))->toBe(substr_count($html, '<template x-if="loaded">'));
});
