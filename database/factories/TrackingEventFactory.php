<?php

namespace Database\Factories;

use App\Enums\TrackingPlatform;
use App\Enums\TrackingStatus;
use App\Models\TrackingEvent;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TrackingEvent>
 */
class TrackingEventFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => (string) Str::uuid(),
            'event_name' => 'Lead',
            'platform' => TrackingPlatform::Meta,
            'kind' => TrackingEvent::KIND_SERVER,
            'status' => TrackingStatus::Sent,
            'response_code' => 200,
            'attempts' => 1,
            'sent_at' => now(),
        ];
    }

    public function failed(): static
    {
        return $this->state(['status' => TrackingStatus::Failed, 'response_code' => 400, 'error' => 'Invalid parameter']);
    }
}
