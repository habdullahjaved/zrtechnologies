<?php

namespace App\Services\Tracking;

use App\Enums\LeadType;
use App\Enums\TrackingPlatform;
use App\Enums\TrackingStatus;
use App\Jobs\SendGa4Event;
use App\Jobs\SendMetaConversion;
use App\Models\Lead;
use App\Models\RepairJob;
use App\Models\TrackingEvent;
use App\Settings\TrackingSettings;
use App\Support\Money;

/**
 * Decides which server-side conversions to send for a lead or a completed
 * repair, records each decision in tracking_events, and queues the sends.
 *
 * - Meta Conversions API: website leads (deduplicated with the browser Pixel
 *   by event_id) and "Purchase" when a repair is completed and paid.
 * - GA4 Measurement Protocol: "purchase" when a repair is completed. Website
 *   leads are already counted by the browser tag, and GA4 cannot deduplicate,
 *   so they are not sent twice.
 * - Google Ads: completed repairs are added to the offline conversions CSV
 *   (gclid / gbraid / wbraid, or hashed email and phone for enhanced conversions).
 *
 * Nothing is sent without the matching consent stored on the lead.
 */
class ConversionDispatcher
{
    public function __construct(
        private readonly TrackingSettings $settings,
        private readonly MetaPayloads $meta,
    ) {}

    /**
     * A new website lead or booking.
     */
    public function lead(Lead $lead, ?string $ipAddress = null): void
    {
        $eventName = $this->metaLeadEvent($lead);

        if ($eventName === null) {
            return;
        }

        $this->queueMeta($lead, null, $lead->event_id, $eventName, fn (): array => $this->meta->forLead($lead, $eventName), $ipAddress);

        if ($lead->type === LeadType::Booking) {
            $this->queueMeta($lead, null, $lead->event_id, 'Schedule', fn (): array => $this->meta->forLead($lead, 'Schedule'), $ipAddress);
        }
    }

    /**
     * A repair marked completed (and paid): the offline conversion.
     */
    public function completedJob(RepairJob $job): void
    {
        $job->loadMissing(['lead', 'customer', 'items']);
        $lead = $job->lead;

        if ($lead === null || $job->final_price_pence === null) {
            return;
        }

        $eventId = 'purchase-'.$job->reference;

        $this->queueMeta($lead, $job, $eventId, 'Purchase', fn (): array => $this->meta->forPurchase($job, $lead, $eventId));
        $this->queueGa4Purchase($lead, $job, $eventId);
        $this->exportGoogleAds($lead, $job, $eventId);
    }

    /**
     * @param  callable(): array<string, mixed>  $payload
     */
    private function queueMeta(Lead $lead, ?RepairJob $job, string $eventId, string $eventName, callable $payload, ?string $ipAddress = null): void
    {
        $status = match (true) {
            ! $this->settings->metaServerConfigured() => TrackingStatus::Skipped,
            ! $this->hasConsent($lead, 'marketing') => TrackingStatus::SkippedNoConsent,
            default => TrackingStatus::Queued,
        };

        $event = $this->record(TrackingPlatform::Meta, $lead, $job, $eventId, $eventName, $status, $status === TrackingStatus::Queued ? $payload() : null);

        if ($event !== null && $status === TrackingStatus::Queued) {
            SendMetaConversion::dispatch($event, $ipAddress);
        }
    }

    private function queueGa4Purchase(Lead $lead, RepairJob $job, string $eventId): void
    {
        $status = match (true) {
            ! $this->settings->ga4ServerConfigured() || blank($lead->ga_client_id) => TrackingStatus::Skipped,
            ! $this->hasConsent($lead, 'analytics') => TrackingStatus::SkippedNoConsent,
            default => TrackingStatus::Queued,
        };

        $payload = $status === TrackingStatus::Queued ? [
            'client_id' => $lead->ga_client_id,
            'timestamp_micros' => (int) (($job->completed_at ?? now())->getTimestamp() * 1_000_000),
            'consent' => [
                'ad_user_data' => $this->hasConsent($lead, 'marketing') ? 'GRANTED' : 'DENIED',
                'ad_personalization' => $this->hasConsent($lead, 'marketing') ? 'GRANTED' : 'DENIED',
            ],
            'events' => [[
                'name' => 'purchase',
                'params' => array_filter([
                    'transaction_id' => $job->reference,
                    'value' => (float) Money::toPounds((int) $job->final_price_pence),
                    'currency' => 'GBP',
                    'session_id' => $lead->ga_session_id,
                    'engagement_time_msec' => 1,
                    'items' => $job->items->map(fn ($item): array => [
                        'item_name' => $item->description,
                        'price' => (float) Money::toPounds((int) $item->price_pence),
                        'quantity' => 1,
                    ])->values()->all(),
                ], fn (mixed $value): bool => $value !== null),
            ]],
        ] : null;

        $event = $this->record(TrackingPlatform::Ga4, $lead, $job, $eventId, 'purchase', $status, $payload);

        if ($event !== null && $status === TrackingStatus::Queued) {
            SendGa4Event::dispatch($event);
        }
    }

    /**
     * Adds the completed repair to the CSV Google Ads collects on a schedule.
     */
    private function exportGoogleAds(Lead $lead, RepairJob $job, string $eventId): void
    {
        $email = $this->settings->enhanced_conversions ? Hasher::email($lead->email ?? $job->customer->email) : null;
        $phone = $this->settings->enhanced_conversions ? Hasher::phone($lead->phone ?? $job->customer->phone) : null;
        $identified = filled($lead->gclid) || filled($lead->gbraid) || filled($lead->wbraid) || $email !== null || $phone !== null;

        $status = match (true) {
            ! $identified => TrackingStatus::Skipped,
            ! $this->hasConsent($lead, 'marketing') => TrackingStatus::SkippedNoConsent,
            default => TrackingStatus::Exported,
        };

        $this->record(TrackingPlatform::GoogleAds, $lead, $job, $eventId, $this->settings->google_ads_offline_conversion_name, $status, $status === TrackingStatus::Exported ? [
            'gclid' => $lead->gclid,
            'gbraid' => $lead->gbraid,
            'wbraid' => $lead->wbraid,
            'email' => $email,
            'phone' => $phone,
            'conversion_time' => ($job->completed_at ?? now())->timezone('Europe/London')->format('Y-m-d H:i:s'),
            'value' => Money::toPounds((int) $job->final_price_pence),
            'currency' => 'GBP',
        ] : null, kind: TrackingEvent::KIND_OFFLINE);
    }

    /**
     * Create the log row once per event, platform and name, so a repeated
     * status change never sends the same conversion twice.
     *
     * @param  array<string, mixed>|null  $payload
     */
    private function record(TrackingPlatform $platform, Lead $lead, ?RepairJob $job, string $eventId, string $eventName, TrackingStatus $status, ?array $payload, string $kind = TrackingEvent::KIND_SERVER): ?TrackingEvent
    {
        $event = TrackingEvent::query()->firstOrNew([
            'event_id' => $eventId,
            'platform' => $platform->value,
            'event_name' => $eventName,
        ]);

        if ($event->exists) {
            return null;
        }

        $event->fill([
            'lead_id' => $lead->getKey(),
            'repair_job_id' => $job?->getKey(),
            'kind' => $job !== null ? TrackingEvent::KIND_OFFLINE : $kind,
            'status' => $status,
            'payload' => $payload,
            'is_test' => $this->settings->test_mode,
        ])->save();

        return $event;
    }

    /**
     * @param  'analytics'|'marketing'  $category
     */
    private function hasConsent(Lead $lead, string $category): bool
    {
        return (bool) ($lead->consent[$category] ?? false);
    }

    /**
     * Meta's standard event for a lead, or null if the lead is not sent.
     */
    private function metaLeadEvent(Lead $lead): ?string
    {
        if ($lead->is_partial) {
            return null;
        }

        return match ($lead->type) {
            LeadType::Booking, LeadType::Quote, LeadType::Callback, LeadType::Business, LeadType::OutOfArea, LeadType::PostIn, LeadType::Chatbot => 'Lead',
            LeadType::Contact, LeadType::CallClick, LeadType::WhatsAppClick => 'Contact',
            LeadType::Manual => null,
        };
    }
}
