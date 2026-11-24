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
 * Sends one event to the Meta Conversions API. The browser Pixel sends the
 * same event with the same event_id, so Meta counts it once.
 *
 * The visitor's IP address travels only in this queued job (never stored)
 * to help Meta match the event.
 */
class SendMetaConversion implements ShouldQueue
{
    use Queueable, SendsTrackingEvent;

    public function __construct(public TrackingEvent $event, public ?string $ipAddress = null) {}

    public function handle(TrackingSettings $settings): void
    {
        if ($this->event->status === TrackingStatus::Sent || ! $settings->metaServerConfigured()) {
            return;
        }

        $data = (array) $this->event->payload;

        if ($this->ipAddress !== null) {
            $data['user_data']['client_ip_address'] = $this->ipAddress;
        }

        $body = array_filter([
            'data' => [$data],
            'test_event_code' => $settings->test_mode && filled($settings->meta_test_event_code) ? $settings->meta_test_event_code : null,
        ]);

        $response = Http::timeout(15)
            ->acceptJson()
            ->post(sprintf(
                '%s/%s/%s/events?access_token=%s',
                rtrim((string) config('services.meta.graph_url'), '/'),
                config('services.meta.graph_version'),
                $settings->meta_pixel_id,
                urlencode((string) $settings->meta_capi_token),
            ), $body);

        $this->recordResponse($this->event, $response);

        if ($this->shouldRetry($response)) {
            throw new RuntimeException('Meta Conversions API returned HTTP '.$response->status());
        }
    }
}
