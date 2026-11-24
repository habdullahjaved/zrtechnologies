<?php

namespace Database\Seeders;

use App\Enums\StaffRole;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * One demo account per role for local development and review.
 * Never runs in production (see DatabaseSeeder).
 */
class DemoStaffSeeder extends Seeder
{
    public const string PASSWORD = 'password';

    /**
     * @var list<array{name: string, email: string, job_title: string, role: StaffRole}>
     */
    private const array STAFF = [
        ['name' => 'Sarah Whitworth', 'email' => 'manager@zrtechnologies.test', 'job_title' => 'Operations Manager', 'role' => StaffRole::Manager],
        ['name' => 'Daniel Haworth', 'email' => 'technician@zrtechnologies.test', 'job_title' => 'Senior Repair Technician', 'role' => StaffRole::Technician],
        ['name' => 'Aisha Patel', 'email' => 'content@zrtechnologies.test', 'job_title' => 'Marketing & Content Lead', 'role' => StaffRole::ContentEditor],
        ['name' => 'James Ainsworth', 'email' => 'sales@zrtechnologies.test', 'job_title' => 'Customer Support Advisor', 'role' => StaffRole::SalesSupport],
    ];

    public function run(): void
    {
        foreach (self::STAFF as $member) {
            $user = User::updateOrCreate(
                ['email' => $member['email']],
                [
                    'name' => $member['name'],
                    'job_title' => $member['job_title'],
                    'is_active' => true,
                    'password' => self::PASSWORD,
                ],
            );

            $user->forceFill(['email_verified_at' => now()])->save();
            $user->syncRoles([$member['role']->value]);
        }

        $this->command?->info('Demo staff created (password: "'.self::PASSWORD.'"): '.implode(', ', array_column(self::STAFF, 'email')));
    }
}
