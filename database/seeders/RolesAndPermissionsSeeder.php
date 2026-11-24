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

        // Phase 2: catalogue, pricing and coverage.
        'DeviceModel' => self::CRUD,
        'RepairType' => self::CRUD,
        'RepairPrice' => self::CRUD,
        'PartQuality' => self::CRUD,
        'ServiceCategory' => self::CRUD,
        'ServiceArea' => self::CRUD,
        'AvailabilityWindow' => self::CRUD,
        'AvailabilityOverride' => self::CRUD,
        'BlackoutDate' => self::CRUD,
        'ManageBookingSettings' => ['View'],

        // Phase 3: CRM and workshop.
        'Lead' => self::CRUD,
        'Customer' => self::CRUD,
        // ViewAll: see every job, not only those assigned to you. ViewFinancials: see prices and totals.
        'RepairJob' => [...self::CRUD, 'ViewAll', 'ViewFinancials'],
        'MessageTemplate' => ['ViewAny', 'View', 'Update'],
        'ManageNotificationSettings' => ['View'],
    ];

    /**
     * @var list<string>
     */
    private const array CRUD = ['ViewAny', 'View', 'Create', 'Update', 'Delete'];

    /**
     * Read-only access to the catalogue and prices, for staff who quote customers.
     *
     * @var list<string>
     */
    private const array VIEW_CATALOGUE = [
        'ViewAny:DeviceModel', 'View:DeviceModel',
        'ViewAny:RepairType', 'View:RepairType',
        'ViewAny:RepairPrice', 'View:RepairPrice',
    ];

    /**
     * Read-only access to service areas and the collection timetable.
     *
     * @var list<string>
     */
    private const array VIEW_COVERAGE = [
        'ViewAny:ServiceArea', 'View:ServiceArea',
        'ViewAny:AvailabilityWindow', 'View:AvailabilityWindow',
        'ViewAny:AvailabilityOverride', 'View:AvailabilityOverride',
        'ViewAny:BlackoutDate', 'View:BlackoutDate',
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
                'View:ManageBookingSettings',
                ...$this->crudFor(['DeviceModel', 'RepairType', 'RepairPrice', 'PartQuality', 'ServiceCategory']),
                ...$this->crudFor(['ServiceArea', 'AvailabilityWindow', 'AvailabilityOverride', 'BlackoutDate']),
                ...$this->crudFor(['Lead', 'Customer', 'RepairJob']),
                'ViewAll:RepairJob', 'ViewFinancials:RepairJob',
                'ViewAny:MessageTemplate', 'View:MessageTemplate', 'Update:MessageTemplate',
                'View:ManageNotificationSettings',
            ],

            // Content editors write repair and model descriptions; they cannot change prices.
            StaffRole::ContentEditor->value => [
                'View:ManageSocialSettings',
                'View:ManageSiteSettings',
                ...self::VIEW_CATALOGUE,
                'Update:DeviceModel',
                'Update:RepairType',
                'ViewAny:ServiceArea', 'View:ServiceArea',
                // Wording of customer emails and WhatsApp replies.
                'ViewAny:MessageTemplate', 'View:MessageTemplate', 'Update:MessageTemplate',
            ],

            // Technicians see and progress the jobs assigned to them, without prices.
            StaffRole::Technician->value => [
                ...self::VIEW_CATALOGUE,
                'ViewAny:RepairJob', 'View:RepairJob', 'Update:RepairJob',
            ],

            // Sales and support work leads and customers and book jobs; they cannot delete records.
            StaffRole::SalesSupport->value => [
                ...self::VIEW_CATALOGUE,
                ...self::VIEW_COVERAGE,
                'ViewAny:Lead', 'View:Lead', 'Create:Lead', 'Update:Lead',
                'ViewAny:Customer', 'View:Customer', 'Create:Customer', 'Update:Customer',
                'ViewAny:RepairJob', 'View:RepairJob', 'Create:RepairJob', 'Update:RepairJob',
                'ViewAll:RepairJob', 'ViewFinancials:RepairJob',
            ],
        ];
    }

    /**
     * @param  list<string>  $subjects
     * @return list<string>
     */
    private function crudFor(array $subjects): array
    {
        $permissions = [];

        foreach ($subjects as $subject) {
            foreach (self::CRUD as $action) {
                $permissions[] = "{$action}:{$subject}";
            }
        }

        return $permissions;
    }
}
