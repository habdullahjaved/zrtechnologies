<?php

namespace App\Policies;

use App\Models\User;
use Spatie\Activitylog\Models\Activity;

/**
 * The audit log is read-only for everyone.
 */
class ActivityPolicy
{
    public function viewAny(User $authUser): bool
    {
        return $authUser->can('ViewAny:Activity');
    }

    public function view(User $authUser, Activity $activity): bool
    {
        return $authUser->can('View:Activity');
    }

    public function create(User $authUser): bool
    {
        return false;
    }

    public function update(User $authUser, Activity $activity): bool
    {
        return false;
    }

    public function delete(User $authUser, Activity $activity): bool
    {
        return false;
    }

    public function deleteAny(User $authUser): bool
    {
        return false;
    }
}
