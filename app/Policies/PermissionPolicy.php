<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Maps the standard resource abilities to Shield-style "Action:Subject"
 * permissions, e.g. viewAny → "ViewAny:RepairPrice". Super admins pass every
 * check through Shield's Gate::before.
 *
 * Concrete policies only name their subject. Permissions are granted to roles
 * in RolesAndPermissionsSeeder.
 */
abstract class PermissionPolicy
{
    /**
     * The permission subject, e.g. "RepairPrice".
     */
    abstract protected function subject(): string;

    public function viewAny(User $authUser): bool
    {
        return $this->allows($authUser, 'ViewAny');
    }

    public function view(User $authUser, Model $record): bool
    {
        return $this->allows($authUser, 'View');
    }

    public function create(User $authUser): bool
    {
        return $this->allows($authUser, 'Create');
    }

    public function update(User $authUser, Model $record): bool
    {
        return $this->allows($authUser, 'Update');
    }

    public function delete(User $authUser, Model $record): bool
    {
        return $this->allows($authUser, 'Delete');
    }

    public function deleteAny(User $authUser): bool
    {
        return $this->allows($authUser, 'Delete');
    }

    /**
     * Drag-and-drop ordering in admin tables counts as editing.
     */
    public function reorder(User $authUser): bool
    {
        return $this->allows($authUser, 'Update');
    }

    protected function allows(User $user, string $action): bool
    {
        return $user->can($action.':'.$this->subject());
    }
}
