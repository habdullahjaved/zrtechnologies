<?php

use App\Models\MessageTemplate;
use App\Models\RepairJob;
use App\Notifications\BookingConfirmed;
use App\Notifications\Channels\StaffWebhookChannel;
use App\Notifications\StaffAlert;
use App\Settings\NotificationSettings;
use App\Support\Messaging\MessageRenderer;
use App\Support\Messaging\MessageTemplateCatalogue;
use App\Support\ReferenceGenerator;
use Database\Seeders\MessageTemplateSeeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\HtmlString;

it('fills placeholders, escapes customer text and drops empty optional lines', function () {
    $rendered = app(MessageRenderer::class)->render("Hi {customer_first_name},\n\n{message}\n\nCall {business_phone}.", [
        'customer_first_name' => '<script>Sam</script>',
        'message' => null,
    ]);

    expect($rendered)->toBe("Hi &lt;script&gt;Sam&lt;/script&gt;,\n\nCall 07360 127538.");
});

it('keeps trusted markdown passed as HTML', function () {
    $rendered = app(MessageRenderer::class)->render('{repairs}', ['repairs' => new HtmlString("- Screen: £89\n- Battery: £69")]);

    expect($rendered)->toBe("- Screen: £89\n- Battery: £69");
});

it('uses the owner’s wording, falls back to the default, and respects switched-off templates', function () {
    $renderer = app(MessageRenderer::class);

    expect($renderer->template(MessageTemplateCatalogue::BOOKING_CONFIRMATION)?->subject)->toBe('Booking confirmed: {reference}');

    $this->seed(MessageTemplateSeeder::class);
    MessageTemplate::query()->where('key', MessageTemplateCatalogue::BOOKING_CONFIRMATION)->update(['subject' => 'All booked! {reference}']);
    expect($renderer->template(MessageTemplateCatalogue::BOOKING_CONFIRMATION)?->subject)->toBe('All booked! {reference}');

    MessageTemplate::query()->where('key', MessageTemplateCatalogue::BOOKING_CONFIRMATION)->update(['is_active' => false]);
    expect($renderer->template(MessageTemplateCatalogue::BOOKING_CONFIRMATION))->toBeNull();
});

it('seeds every template once and keeps edits on re-seeding', function () {
    $this->seed(MessageTemplateSeeder::class);
    MessageTemplate::query()->where('key', MessageTemplateCatalogue::ENQUIRY_RECEIVED)->update(['body' => 'Edited']);
    $this->seed(MessageTemplateSeeder::class);

    expect(MessageTemplate::query()->count())->toBe(count(MessageTemplateCatalogue::all()))
        ->and(MessageTemplate::query()->where('key', MessageTemplateCatalogue::ENQUIRY_RECEIVED)->value('body'))->toBe('Edited');
});

it('renders the branded confirmation email', function () {
    $job = RepairJob::factory()->create(['reference' => 'ZR-2026-000123']);
    $job->items()->create(['description' => 'Screen Replacement (Premium)', 'price_pence' => 14900, 'warranty_days' => 365]);

    $html = (string) (new BookingConfirmed($job))->toMail($job->customer)->render();

    expect($html)->toContain('ZR-2026-000123')
        ->toContain('Screen Replacement (Premium): £149')
        ->toContain('Track your repair')
        ->toContain('company number 17589309')
        ->toContain('zr-logo-email-signature-300w.png');
});

it('posts team alerts to Slack and Telegram when set up', function () {
    Http::fake();

    $settings = app(NotificationSettings::class);
    $settings->slack_webhook_url = 'https://hooks.slack.com/services/T000/B000/XXX';
    $settings->telegram_bot_token = '123:abc';
    $settings->telegram_chat_id = '-1001';
    $settings->save();

    $alert = new StaffAlert(MessageTemplateCatalogue::STAFF_NEW_ENQUIRY, ['customer_name' => 'Sarah', 'lead_type' => 'callback request', 'reference' => 'ENQ-2026-000001'], 'https://example.test/admin/leads/1');

    expect($alert->via(Notification::route('mail', ['team@example.com'])))->toContain(StaffWebhookChannel::class);

    app(StaffWebhookChannel::class)->send(Notification::route('mail', ['team@example.com']), $alert);

    Http::assertSent(fn ($request) => str_starts_with($request->url(), 'https://hooks.slack.com/') && str_contains($request['text'], 'New callback request: Sarah'));
    Http::assertSent(fn ($request) => str_contains($request->url(), 'api.telegram.org/bot123:abc/sendMessage') && $request['chat_id'] === '-1001');
});

it('numbers references in sequence, restarting each year', function () {
    $references = app(ReferenceGenerator::class);

    expect($references->next('job', 'ZR', 2026))->toBe('ZR-2026-000001')
        ->and($references->next('job', 'ZR', 2026))->toBe('ZR-2026-000002')
        ->and($references->next('lead', 'ENQ', 2026))->toBe('ENQ-2026-000001')
        ->and($references->next('job', 'ZR', 2027))->toBe('ZR-2027-000001');
});
