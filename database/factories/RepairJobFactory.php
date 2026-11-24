<?php

namespace Database\Factories;

use App\Enums\JobStatus;
use App\Models\Customer;
use App\Models\RepairJob;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RepairJob>
 */
class RepairJobFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference' => 'ZR-'.now()->format('Y').'-'.fake()->unique()->numerify('######'),
            'status' => JobStatus::Booked,
            'customer_id' => Customer::factory(),
            'reported_fault' => 'Cracked screen',
            'quoted_price_pence' => 8900,
            'collection_fee_pence' => 0,
            'booked_at' => now(),
        ];
    }

    public function status(JobStatus $status): static
    {
        return $this->state(['status' => $status]);
    }
}
