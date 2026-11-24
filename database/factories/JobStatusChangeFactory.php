<?php

namespace Database\Factories;

use App\Enums\JobStatus;
use App\Models\JobStatusChange;
use App\Models\RepairJob;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobStatusChange>
 */
class JobStatusChangeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'repair_job_id' => RepairJob::factory(),
            'from_status' => null,
            'to_status' => JobStatus::Booked,
            'customer_notified' => false,
        ];
    }
}
