<?php

namespace App\Policies;

use App\Enums\StaffRole;
use App\Models\User;
use Spatie\Permission\Models\Role;

/**
 * Roles. The built-in super admin role can never be edited or deleted.
 */
class RolePolicy
{
    public function viewAny(User $authUser): bool
    {
        return $authUser->can('ViewAny:Role');
    }

    public function view(User $authUser, Role $role): bool
    {
        return $authUser->can('View:Role');
    }

    public function create(User $authUser): bool
    {
        return $authUser->can('Create:Role');
    }

    public function update(User $authUser, Role $role): bool
    {
        return $authUser->can('Update:Role') && $role->name !== StaffRole::SuperAdmin->value;
    }

    public function delete(User $authUser, Role $role): bool
    {
        return $authUser->can('Delete:Role')
            && ! in_array($role->name, array_column(StaffRole::cases(), 'value'), true);
    }

    public function deleteAny(User $authUser): bool
    {
        return false;
    }
}
