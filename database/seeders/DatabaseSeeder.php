<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Seeds everything the application needs.
 *
 * All environments: roles/permissions, the owner's super admin account, and
 * the draft catalogue, prices, service areas and collection timetable, the
 * message templates and the website content (pages, legal drafts, repair
 * pages, FAQs, blog drafts, video placeholders, menus); all only created when
 * missing, so the owner's edits survive re-seeding.
 * Local/testing: also one demo account per staff role, sample CRM records
 * (names start with "Demo") and sample reviews (flagged, never shown in production).
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
            CatalogueSeeder::class,
            CoverageSeeder::class,
            MessageTemplateSeeder::class,
            ContentSeeder::class,
        ]);

        if (! app()->isProduction()) {
            $this->call([DemoStaffSeeder::class, DemoCrmSeeder::class, DemoContentSeeder::class]);
        }
    }
}
