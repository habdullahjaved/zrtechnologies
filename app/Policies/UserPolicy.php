<?php

namespace App\Policies;

use App\Models\User;

/**
 * Staff accounts. Permissions come from Shield; the record checks below stop
 * anyone deleting themselves, and stop non-super-admins changing a super admin.
 *
 * Super admins pass every check through Shield's Gate::before, so the UI also
 * hides self-deletion (see EditUser).
 */
class UserPolicy
{
    public function viewAny(User $authUser): bool
    {
        return $authUser->can('ViewAny:User');
    }

    public function view(User $authUser, User $user): bool
    {
        return $authUser->can('View:User');
    }

    public function create(User $authUser): bool
    {
        return $authUser->can('Create:User');
    }

    public function update(User $authUser, User $user): bool
    {
        return $authUser->can('Update:User') && $this->canManage($authUser, $user);
    }

    public function delete(User $authUser, User $user): bool
    {
        return $authUser->can('Delete:User')
            && ! $authUser->is($user)
            && $this->canManage($authUser, $user);
    }

    public function restore(User $authUser, User $user): bool
    {
        return $authUser->can('Restore:User') && $this->canManage($authUser, $user);
    }

    public function forceDelete(User $authUser, User $user): bool
    {
        return $authUser->can('ForceDelete:User')
            && ! $authUser->is($user)
            && $this->canManage($authUser, $user);
    }

    /**
     * Only a super admin may change another super admin's account.
     */
    private function canManage(User $authUser, User $user): bool
    {
        return ! $user->isSuperAdmin() || $authUser->isSuperAdmin();
    }
}
