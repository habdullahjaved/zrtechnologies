<?php

namespace Database\Seeders;

use App\Enums\StaffRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Staff roles and what each may do. Safe to run repeatedly: roles and
 * permissions are created if missing and each role's permissions are synced.
 *
 * Permission names follow Filament Shield's convention: "Action:Model" for
 * resources and "View:PageClass" for pages. Each phase adds its own here.
 */
class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Every permission in the system, grouped by subject.
     *
     * @var array<string, list<string>>
     */
    public const array PERMISSIONS = [
        'User' => ['ViewAny', 'View', 'Create', 'Update', 'Delete', 'Restore', 'ForceDelete'],
        'Role' => ['ViewAny', 'View', 'Create', 'Update', 'Delete'],
        'Activity' => ['ViewAny', 'View'],
        'ManageBusinessSettings' => ['View'],
        'ManageOpeningHoursSettings' => ['View'],
        'ManageSocialSettings' => ['View'],
        'ManageSiteSettings' => ['View'],
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $all = [];

        foreach (self::PERMISSIONS as $subject => $actions) {
            foreach ($actions as $action) {
                $all[] = Permission::findOrCreate("{$action}:{$subject}", 'web')->name;
            }
        }

        // Model events are disabled while seeding, so refresh the permission cache by hand.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($this->rolePermissions($all) as $roleName => $permissions) {
            Role::findOrCreate($roleName, 'web')->syncPermissions($permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * @param  list<string>  $all
     * @return array<string, list<string>>
     */
    private function rolePermissions(array $all): array
    {
        return [
            // Super admins also pass every check through Shield's Gate::before.
            StaffRole::SuperAdmin->value => $all,

            StaffRole::Manager->value => [
                'ViewAny:User', 'View:User', 'Create:User', 'Update:User', 'Delete:User', 'Restore:User',
                'ViewAny:Role', 'View:Role',
                'ViewAny:Activity', 'View:Activity',
                'View:ManageBusinessSettings',
                'View:ManageOpeningHoursSettings',
                'View:ManageSocialSettings',
                'View:ManageSiteSettings',
            ],

            StaffRole::ContentEditor->value => [
                'View:ManageSocialSettings',
                'View:ManageSiteSettings',
            ],

            // Technician and Sales/Support permissions arrive with the workshop and CRM (Phase 3).
            StaffRole::Technician->value => [],
            StaffRole::SalesSupport->value => [],
        ];
    }
}
