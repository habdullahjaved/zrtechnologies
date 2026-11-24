<?php

namespace App\Http\Controllers;

use App\Enums\TrackingPlatform;
use App\Enums\TrackingStatus;
use App\Models\TrackingEvent;
use App\Settings\TrackingSettings;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * GET /exports/offline-conversions/google-ads.csv?token=…
 *
 * Completed, paid repairs from the last 90 days in Google Ads' offline
 * conversion import format, for a scheduled upload (Google Ads → Goals →
 * Conversions → Uploads → Schedules, source "HTTPS"). The token in the link is
 * the only key, so treat the link like a password.
 *
 * Google Ads ignores rows it has already imported, so the same file can be
 * collected every day.
 */
class OfflineConversionExportController extends Controller
{
    /** Google Ads only accepts clicks from the last 90 days. */
    private const int DAYS = 90;

    public const array COLUMNS = ['Google Click ID', 'GBRAID', 'WBRAID', 'Email', 'Phone Number', 'Conversion Name', 'Conversion Time', 'Conversion Value', 'Conversion Currency'];

    public function __invoke(Request $request, TrackingSettings $settings): Response
    {
        abort_unless(is_string($request->query('token')) && hash_equals($settings->export_token, $request->query('token')), 404);

        return response(self::csv(), 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'inline; filename="google-ads-offline-conversions.csv"',
            'Cache-Control' => 'no-store',
            'X-Robots-Tag' => 'noindex',
        ]);
    }

    public static function csv(): string
    {
        $handle = fopen('php://temp', 'r+');

        if ($handle === false) {
            return '';
        }

        fwrite($handle, "Parameters:TimeZone=Europe/London\n");
        fputcsv($handle, self::COLUMNS, escape: '');

        $events = TrackingEvent::query()
            ->where('platform', TrackingPlatform::GoogleAds->value)
            ->where('status', TrackingStatus::Exported->value)
            ->where('created_at', '>=', now()->subDays(self::DAYS))
            ->orderBy('id')
            ->get();

        foreach ($events as $event) {
            $row = (array) $event->payload;

            fputcsv($handle, [
                $row['gclid'] ?? '',
                $row['gbraid'] ?? '',
                $row['wbraid'] ?? '',
                $row['email'] ?? '',
                $row['phone'] ?? '',
                $event->event_name,
                $row['conversion_time'] ?? '',
                $row['value'] ?? '',
                $row['currency'] ?? 'GBP',
            ], escape: '');
        }

        rewind($handle);
        $csv = (string) stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }

    public static function url(TrackingSettings $settings): string
    {
        return route('exports.offline-conversions', ['token' => $settings->export_token]);
    }
}
