<?php

namespace Database\Factories;

use App\Enums\Channel;
use App\Enums\LeadStatus;
use App\Enums\LeadType;
use App\Models\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Lead>
 */
class LeadFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference' => 'ENQ-'.now()->format('Y').'-'.fake()->unique()->numerify('######'),
            'type' => LeadType::Contact,
            'status' => LeadStatus::New,
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '+4477'.fake()->numerify('########'),
            'postcode' => 'BB2 4NY',
            'message' => fake()->sentence(12),
            'channel' => Channel::Direct,
            'first_touch_channel' => Channel::Direct,
            'event_id' => (string) Str::uuid(),
        ];
    }

    public function type(LeadType $type): static
    {
        return $this->state(['type' => $type]);
    }

    public function status(LeadStatus $status): static
    {
        return $this->state(['status' => $status]);
    }

    public function partialBooking(): static
    {
        return $this->state(['type' => LeadType::Booking, 'is_partial' => true, 'name' => null, 'message' => null]);
    }

    public function fromGoogleAds(): static
    {
        return $this->state([
            'channel' => Channel::GoogleAds,
            'gclid' => 'TEST'.fake()->numerify('#########'),
            'utm_source' => 'google',
            'utm_medium' => 'cpc',
            'utm_campaign' => 'iphone-repair-blackburn',
        ]);
    }
}
