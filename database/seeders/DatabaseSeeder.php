<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Seeds everything the application needs.
 *
 * Production: roles/permissions and the owner's super admin account only.
 * Local/testing: also one demo account per staff role.
 *
 * Business settings (NAP, opening hours, SEO defaults) are created by the
 * settings migrations in database/settings, so they exist after `migrate`.
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            AdminUserSeeder::class,
        ]);

        if (! app()->isProduction()) {
            $this->call(DemoStaffSeeder::class);
        }
    }
}
