<?php

use App\Actions\Jobs\ChangeJobStatus;
use App\Enums\JobStatus;
use App\Enums\StaffRole;
use App\Enums\SystemPage;
use App\Filament\Pages\Settings\ManageConsentSettings;
use App\Filament\Pages\Settings\ManageTrackingSettings;
use App\Filament\Pages\TrackingHealth;
use App\Filament\Resources\ConsentRecords\ConsentRecordResource;
use App\Filament\Resources\TrackingEvents\TrackingEventResource;
use App\Models\Customer;
use App\Models\Page;
use App\Models\RepairJob;
use App\Models\RepairPrice;
use App\Models\TrackingEvent;
use App\Models\User;
use App\Notifications\ReviewRequest;
use App\Settings\SocialSettings;
use Database\Seeders\MessageTemplateSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Notification;

it('asks for a Google review once, a few hours after a repair is completed', function () {
    Notification::fake();
    $this->seed(MessageTemplateSeeder::class);
    $social = app(SocialSettings::class);
    $social->google_review_url = 'https://g.page/r/abc/review';
    $social->save();

    $job = RepairJob::factory()->for(Customer::factory()->create(['email' => 'sarah@example.com']))->status(JobStatus::Returned)->create(['final_price_pence' => 6900]);

    app(ChangeJobStatus::class)->handle($job, JobStatus::Completed, notifyCustomer: false);

    Notification::assertSentTo($job->customer, ReviewRequest::class, fn (ReviewRequest $notification): bool => $notification->delay !== null);
    expect($job->fresh()->review_requested_at)->not->toBeNull();

    $mail = (new ReviewRequest($job))->toMail($job->customer);
    expect($mail->viewData['actionUrl'])->toBe(route('reviews.leave'))
        ->and($mail->subject)->toContain($job->customer->first_name);
});

it('does not ask for reviews until the Google review link is set', function () {
    Notification::fake();
    $job = RepairJob::factory()->status(JobStatus::Returned)->create(['final_price_pence' => 6900]);

    app(ChangeJobStatus::class)->handle($job, JobStatus::Completed, notifyCustomer: false);

    Notification::assertNotSentTo($job->customer, ReviewRequest::class);
});

it('sends a Content-Security-Policy with a per-request nonce on public pages', function () {
    $response = $this->get(route('home'))->assertOk();
    $policy = (string) $response->headers->get('Content-Security-Policy');

    preg_match("/'nonce-([^']+)'/", $policy, $matches);

    expect($policy)->toContain("default-src 'self'")
        ->toContain("'strict-dynamic'")
        ->toContain("object-src 'none'")
        ->toContain("frame-ancestors 'self'")
        ->and($matches[1] ?? null)->not->toBeNull()
        ->and($response->getContent())->toContain('nonce="'.$matches[1].'"');

    $second = $this->get(route('home'))->headers->get('Content-Security-Policy');
    expect($second)->not->toBe($policy);
});

it('gives cached pages the current visitor’s nonce', function () {
    config(['zr.page_cache.enabled' => true]);
    Page::factory()->system(SystemPage::About)->create();

    $this->get(route('about'))->assertHeader('X-Page-Cache', 'MISS');
    $cached = $this->get(route('about'))->assertHeader('X-Page-Cache', 'HIT');

    preg_match("/'nonce-([^']+)'/", (string) $cached->headers->get('Content-Security-Policy'), $matches);

    expect($cached->getContent())->toContain('nonce="'.$matches[1].'"')
        ->not->toContain('__ZR_CSP_NONCE__');
});

it('blocks the launch while prices and legal pages are still drafts', function () {
    RepairPrice::factory()->create(['is_draft' => true]);

    $this->artisan('launch:check')
        ->expectsOutputToContain('Prices are confirmed')
        ->assertFailed();
});

it('gives managers and the marketing team the tracking screens, and keeps others out', function (StaffRole $role, bool $allowed) {
    $this->seed(RolesAndPermissionsSeeder::class);
    config(['zr.admin.require_mfa' => false]);
    TrackingEvent::factory()->create();
    $this->actingAs(User::factory()->withRole($role)->create());

    foreach ([TrackingHealth::getUrl(), ManageTrackingSettings::getUrl(), ManageConsentSettings::getUrl(), TrackingEventResource::getUrl('index'), ConsentRecordResource::getUrl('index')] as $url) {
        $this->get($url)->assertStatus($allowed ? 200 : 403);
    }
})->with([
    'manager' => [StaffRole::Manager, true],
    'marketing / content' => [StaffRole::ContentEditor, true],
    'sales / support' => [StaffRole::SalesSupport, false],
    'technician' => [StaffRole::Technician, false],
]);

it('shows the offline conversions link and Google Business Profile links on Tracking health', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    config(['zr.admin.require_mfa' => false]);
    $this->actingAs(User::factory()->withRole(StaffRole::Manager)->create());

    $this->get(TrackingHealth::getUrl())
        ->assertOk()
        ->assertSee('exports/offline-conversions/google-ads.csv', false)
        ->assertSee('utm_campaign=gbp', false);
});
