<?php

namespace Database\Seeders;

use App\Enums\StaffRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Creates the owner's super admin account from config/zr.php (ADMIN_* env vars).
 *
 * Re-running never changes an existing account's password. When no password is
 * configured, a strong random one is generated and printed once.
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $config = config('zr.super_admin');

        $user = User::withTrashed()->firstWhere('email', $config['email']);

        if ($user) {
            $user->restore();
            $user->assignRole(StaffRole::SuperAdmin->value);
            $this->command?->info("Super admin {$user->email} already exists; role confirmed, password unchanged.");

            return;
        }

        $password = filled($config['password']) ? $config['password'] : Str::password(20);

        $user = User::create([
            'name' => $config['name'],
            'email' => $config['email'],
            'job_title' => 'Director',
            'phone' => '+447360127538',
            'is_active' => true,
            'password' => $password,
        ]);

        $user->forceFill(['email_verified_at' => now()])->save();
        $user->assignRole(StaffRole::SuperAdmin->value);

        $this->command?->info("Super admin created: {$user->email}");

        if (blank($config['password'])) {
            $this->command?->warn("Generated password (shown once, store it safely): {$password}");
        }
    }
}
