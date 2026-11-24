<?php

namespace App\Jobs;

use App\Enums\TrackingStatus;
use App\Jobs\Concerns\SendsTrackingEvent;
use App\Models\TrackingEvent;
use App\Settings\TrackingSettings;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Sends one event to GA4 through the Measurement Protocol, using the
 * visitor's GA client ID so it joins their website journey.
 *
 * In test mode the debug endpoint is used: GA4 validates the event and
 * reports problems, but does not record it.
 */
class SendGa4Event implements ShouldQueue
{
    use Queueable, SendsTrackingEvent;

    public function __construct(public TrackingEvent $event) {}

    public function handle(TrackingSettings $settings): void
    {
        if ($this->event->status === TrackingStatus::Sent || ! $settings->ga4ServerConfigured()) {
            return;
        }

        $response = Http::timeout(15)
            ->acceptJson()
            ->post(sprintf(
                '%s/%smp/collect?measurement_id=%s&api_secret=%s',
                rtrim((string) config('services.ga4.url'), '/'),
                $settings->test_mode ? 'debug/' : '',
                urlencode((string) $settings->ga4_measurement_id),
                urlencode((string) $settings->ga4_api_secret),
            ), (array) $this->event->payload);

        $this->recordResponse($this->event, $response);

        // The debug endpoint always answers 200 and lists any problems instead.
        $problem = $response->json('validationMessages.0.description');

        if (is_string($problem)) {
            $this->event->forceFill(['status' => TrackingStatus::Failed, 'error' => mb_substr($problem, 0, 490)])->save();
        }

        if ($this->shouldRetry($response)) {
            throw new RuntimeException('GA4 Measurement Protocol returned HTTP '.$response->status());
        }
    }
}
