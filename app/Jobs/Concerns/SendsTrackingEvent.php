<?php

namespace App\Jobs\Concerns;

use App\Enums\TrackingStatus;
use App\Models\TrackingEvent;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Str;
use Throwable;

/**
 * Shared retry and logging behaviour for server-side conversion sends:
 * five attempts over about an hour, and every outcome written to the
 * tracking_events row shown on the Tracking Health page.
 */
trait SendsTrackingEvent
{
    public int $tries = 5;

    /**
     * Seconds to wait before each retry.
     *
     * @return list<int>
     */
    public function backoff(): array
    {
        return [60, 300, 900, 1800];
    }

    protected function recordResponse(TrackingEvent $event, Response $response): void
    {
        $event->forceFill([
            'attempts' => $event->attempts + 1,
            'response_code' => $response->status(),
            'response_body' => Str::limit($response->body(), 2000),
            'status' => $response->successful() ? TrackingStatus::Sent : TrackingStatus::Failed,
            'error' => $response->successful() ? null : Str::limit($this->errorMessage($response), 490),
            'sent_at' => $response->successful() ? now() : $event->sent_at,
        ])->save();
    }

    /**
     * Server errors and rate limits are retried; bad requests are not (they would fail again).
     */
    protected function shouldRetry(Response $response): bool
    {
        return $response->serverError() || $response->status() === 429;
    }

    public function failed(?Throwable $exception): void
    {
        $this->event->forceFill([
            'status' => TrackingStatus::Failed,
            'error' => Str::limit($exception?->getMessage() ?? 'Failed after all retries', 490),
        ])->save();
    }

    private function errorMessage(Response $response): string
    {
        $json = $response->json();

        if (is_array($json)) {
            $message = data_get($json, 'error.message') ?? data_get($json, 'validationMessages.0.description');

            if (is_string($message)) {
                return $message;
            }
        }

        return 'HTTP '.$response->status();
    }
}
