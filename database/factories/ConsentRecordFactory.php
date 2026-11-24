<?php

namespace Database\Factories;

use App\Models\ConsentRecord;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ConsentRecord>
 */
class ConsentRecordFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'consent_id' => (string) Str::uuid(),
            'action' => ConsentRecord::ACCEPT_ALL,
            'analytics' => true,
            'marketing' => true,
            'policy_version' => '2026-11',
            'page' => '/',
        ];
    }
}
