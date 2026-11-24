<?php

namespace App\Filament\Pages;

use App\Enums\TrackingPlatform;
use App\Enums\TrackingStatus;
use App\Filament\NavigationGroup;
use App\Http\Controllers\OfflineConversionExportController;
use App\Models\ConsentRecord;
use App\Models\TrackingEvent;
use App\Settings\SocialSettings;
use App\Settings\TrackingSettings;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;
use UnitEnum;

/**
 * One screen to check tracking works: which platforms are set up, what was
 * sent in the last week, cookie choices, test sends, the offline conversions
 * link and ready-made Google Business Profile links.
 */
class TrackingHealth extends Page
{
    use HasPageShield;

    protected string $view = 'filament.pages.tracking-health';

    protected static ?string $slug = 'tracking-health';

    protected static ?string $navigationLabel = 'Tracking health';

    protected static ?string $title = 'Tracking health';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHeart;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Marketing;

    protected static ?int $navigationSort = 10;

    /**
     * @return list<array{label: string, ok: bool, detail: string}>
     */
    public function platforms(): array
    {
        $settings = app(TrackingSettings::class);

        return [
            ['label' => 'Google Tag Manager', 'ok' => filled($settings->gtm_container_id), 'detail' => $settings->gtm_container_id ?: 'Not used: the website loads tags itself'],
            ['label' => 'Google Analytics 4 (website)', 'ok' => filled($settings->ga4_measurement_id), 'detail' => $settings->ga4_measurement_id ?: 'Add the measurement ID'],
            ['label' => 'Google Analytics 4 (server: completed repairs)', 'ok' => $settings->ga4ServerConfigured(), 'detail' => $settings->ga4ServerConfigured() ? 'Measurement Protocol ready' : 'Add the API secret'],
            ['label' => 'Google Ads conversions', 'ok' => filled($settings->google_ads_conversion_id), 'detail' => $settings->google_ads_conversion_id ?: 'Add the conversion ID and labels'],
            ['label' => 'Meta Pixel (website)', 'ok' => filled($settings->meta_pixel_id), 'detail' => $settings->meta_pixel_id ?: 'Add the Pixel ID'],
            ['label' => 'Meta Conversions API (server)', 'ok' => $settings->metaServerConfigured(), 'detail' => $settings->metaServerConfigured() ? 'Ready' : 'Add the access token'],
            ['label' => 'Microsoft Clarity', 'ok' => filled($settings->clarity_project_id), 'detail' => $settings->clarity_project_id ?: 'Optional'],
            ['label' => 'Test mode', 'ok' => ! $settings->test_mode, 'detail' => $settings->test_mode ? 'On: switch off before launch' : 'Off (live)'],
        ];
    }

    /**
     * Server-side sends in the last 7 days, by platform and status.
     *
     * @return array<string, array<string, int>>
     */
    public function lastWeek(): array
    {
        $counts = TrackingEvent::query()
            ->where('created_at', '>=', now()->subDays(7))
            ->selectRaw('platform, status, COUNT(*) as total')
            ->groupBy('platform', 'status')
            ->get();

        $summary = [];

        foreach (TrackingPlatform::cases() as $platform) {
            foreach (TrackingStatus::cases() as $status) {
                $total = (int) $counts->first(fn ($row): bool => $row->platform === $platform && $row->status === $status)?->getAttribute('total');

                if ($total > 0) {
                    $summary[$platform->getLabel()][$status->getLabel()] = $total;
                }
            }
        }

        return $summary;
    }

    /**
     * Cookie choices in the last 30 days.
     *
     * @return array{accepted: int, rejected: int, custom: int}
     */
    public function consentSummary(): array
    {
        $counts = ConsentRecord::query()
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('action, COUNT(*) as total')
            ->groupBy('action')
            ->pluck('total', 'action');

        return [
            'accepted' => (int) ($counts[ConsentRecord::ACCEPT_ALL] ?? 0),
            'rejected' => (int) ($counts[ConsentRecord::REJECT_ALL] ?? 0),
            'custom' => (int) ($counts[ConsentRecord::CUSTOM] ?? 0),
        ];
    }

    /**
     * Links for the Google Business Profile, tagged so visits are credited to GBP.
     *
     * @return list<array{label: string, url: string}>
     */
    public function googleBusinessLinks(): array
    {
        $tag = fn (string $url, string $content): string => $url.'?'.http_build_query([
            'utm_source' => 'google',
            'utm_medium' => 'organic',
            'utm_campaign' => 'gbp',
            'utm_content' => $content,
        ]);

        return [
            ['label' => 'Website button', 'url' => $tag(route('home'), 'website')],
            ['label' => 'Booking / appointment link', 'url' => $tag(route('booking.create'), 'booking')],
            ['label' => 'Posts ("Learn more" button)', 'url' => $tag(route('repairs.index'), 'post')],
        ];
    }

    public function offlineExportUrl(): string
    {
        return OfflineConversionExportController::url(app(TrackingSettings::class));
    }

    public function reviewLinkSet(): bool
    {
        return filled(app(SocialSettings::class)->google_review_url);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('testMeta')
                ->label('Send Meta test event')
                ->icon(Heroicon::OutlinedPaperAirplane)
                ->visible(fn (): bool => app(TrackingSettings::class)->metaServerConfigured())
                ->action(fn () => $this->sendMetaTest()),
            Action::make('testGa4')
                ->label('Validate GA4 server event')
                ->icon(Heroicon::OutlinedCheckBadge)
                ->color('gray')
                ->visible(fn (): bool => app(TrackingSettings::class)->ga4ServerConfigured())
                ->action(fn () => $this->validateGa4()),
            Action::make('downloadOffline')
                ->label('Download offline conversions')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('gray')
                ->url(fn (): string => $this->offlineExportUrl(), shouldOpenInNewTab: true),
        ];
    }

    /**
     * Sends one PageView with the test event code: it appears in Events
     * Manager → Test events within a minute and is never counted as real.
     */
    private function sendMetaTest(): void
    {
        $settings = app(TrackingSettings::class);

        if (blank($settings->meta_test_event_code)) {
            Notification::make()->title('Add a test event code first')->body('Copy it from Meta Events Manager → Test events, then save it in Settings → Tracking.')->warning()->send();

            return;
        }

        try {
            $response = Http::timeout(15)->post(sprintf(
                '%s/%s/%s/events?access_token=%s',
                rtrim((string) config('services.meta.graph_url'), '/'),
                config('services.meta.graph_version'),
                $settings->meta_pixel_id,
                urlencode((string) $settings->meta_capi_token),
            ), [
                'data' => [[
                    'event_name' => 'PageView',
                    'event_time' => now()->getTimestamp(),
                    'event_id' => 'test-'.Str::uuid(),
                    'action_source' => 'website',
                    'event_source_url' => url('/'),
                    'user_data' => ['client_user_agent' => 'ZR Technologies tracking test', 'country' => [hash('sha256', 'gb')]],
                ]],
                'test_event_code' => $settings->meta_test_event_code,
            ]);
        } catch (Throwable $exception) {
            Notification::make()->title('Could not reach Meta')->body($exception->getMessage())->danger()->send();

            return;
        }

        $response->successful()
            ? Notification::make()->title('Test event sent')->body('Check Events Manager → Test events.')->success()->send()
            : Notification::make()->title('Meta rejected the test event')->body((string) ($response->json('error.message') ?? 'HTTP '.$response->status()))->danger()->send();
    }

    /**
     * Asks GA4's validation endpoint to check a sample purchase event.
     */
    private function validateGa4(): void
    {
        $settings = app(TrackingSettings::class);

        try {
            $response = Http::timeout(15)->post(sprintf(
                '%s/debug/mp/collect?measurement_id=%s&api_secret=%s',
                rtrim((string) config('services.ga4.url'), '/'),
                urlencode((string) $settings->ga4_measurement_id),
                urlencode((string) $settings->ga4_api_secret),
            ), [
                'client_id' => '123456789.1700000000',
                'events' => [['name' => 'purchase', 'params' => ['transaction_id' => 'TEST', 'value' => 1, 'currency' => 'GBP']]],
            ]);
        } catch (Throwable $exception) {
            Notification::make()->title('Could not reach Google')->body($exception->getMessage())->danger()->send();

            return;
        }

        $problem = $response->json('validationMessages.0.description');

        $response->successful() && ! is_string($problem)
            ? Notification::make()->title('GA4 accepted the test event')->success()->send()
            : Notification::make()->title('GA4 found a problem')->body(is_string($problem) ? $problem : 'HTTP '.$response->status())->danger()->send();
    }
}
