<?php

namespace App\Services\Tracking;

use App\Models\Lead;
use App\Models\RepairJob;
use App\Support\Money;

/**
 * Builds Meta Conversions API events. Personal data is normalised and
 * SHA-256 hashed here; the plain values are never sent or logged.
 *
 * @see https://developers.facebook.com/docs/marketing-api/conversions-api/parameters
 */
class MetaPayloads
{
    /**
     * @return array<string, mixed>
     */
    public function forLead(Lead $lead, string $eventName): array
    {
        $lead->loadMissing('customer');
        [$firstName, $lastName] = $this->names($lead);

        return [
            'event_name' => $eventName,
            'event_time' => $lead->created_at?->getTimestamp() ?? now()->getTimestamp(),
            'event_id' => $lead->event_id,
            'action_source' => 'website',
            'event_source_url' => $this->sourceUrl($lead),
            'user_data' => $this->userData($lead, $firstName, $lastName),
            'custom_data' => array_filter([
                'lead_type' => $lead->type->value,
                'currency' => 'GBP',
                'value' => $lead->estimated_value_pence !== null ? (float) Money::toPounds($lead->estimated_value_pence) : null,
            ], fn (mixed $value): bool => $value !== null),
        ];
    }

    /**
     * The offline "repair completed and paid" conversion.
     *
     * @return array<string, mixed>
     */
    public function forPurchase(RepairJob $job, Lead $lead, string $eventId): array
    {
        $job->loadMissing(['customer', 'items']);
        $lead->loadMissing('customer');
        [$firstName, $lastName] = $this->names($lead);

        return [
            'event_name' => 'Purchase',
            'event_time' => ($job->completed_at ?? now())->getTimestamp(),
            'event_id' => $eventId,
            'action_source' => 'system_generated',
            'user_data' => $this->userData($lead, $firstName ?? $job->customer->first_name, $lastName ?? $job->customer->last_name, $job->customer->email, $job->customer->phone),
            'custom_data' => [
                'currency' => 'GBP',
                'value' => (float) Money::toPounds((int) $job->final_price_pence),
                'order_id' => $job->reference,
                'contents' => $job->items->map(fn ($item): array => ['id' => (string) ($item->repair_type_id ?? $item->id), 'quantity' => 1, 'item_price' => (float) Money::toPounds((int) $item->price_pence)])->values()->all(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function userData(Lead $lead, ?string $firstName, ?string $lastName, ?string $fallbackEmail = null, ?string $fallbackPhone = null): array
    {
        $values = [
            'em' => Hasher::email($lead->email ?? $fallbackEmail),
            'ph' => Hasher::phone($lead->phone ?? $fallbackPhone),
            'fn' => Hasher::name($firstName),
            'ln' => Hasher::name($lastName),
            'zp' => Hasher::postcode($lead->postcode),
            'country' => Hasher::hash('gb'),
            'external_id' => $lead->customer_id !== null ? Hasher::hash('customer-'.$lead->customer_id) : null,
        ];

        $userData = [];

        foreach ($values as $key => $hash) {
            if ($hash !== null) {
                $userData[$key] = [$hash];
            }
        }

        foreach (['fbc' => $lead->fbc, 'fbp' => $lead->fbp, 'client_user_agent' => $lead->user_agent] as $key => $value) {
            if (filled($value)) {
                $userData[$key] = $value;
            }
        }

        return $userData;
    }

    /**
     * @return array{0: string|null, 1: string|null}
     */
    private function names(Lead $lead): array
    {
        if ($lead->customer !== null) {
            return [$lead->customer->first_name, $lead->customer->last_name];
        }

        if (blank($lead->name)) {
            return [null, null];
        }

        $parts = preg_split('/\s+/', trim((string) $lead->name)) ?: [];

        return [$parts[0] ?? null, count($parts) > 1 ? end($parts) : null];
    }

    private function sourceUrl(Lead $lead): string
    {
        $pages = $lead->attribution['pages'] ?? [];
        $last = is_array($pages) && $pages !== [] ? end($pages) : null;
        $path = is_array($last) && is_string($last['path'] ?? null) ? $last['path'] : '/';

        return url($path);
    }
}
