<?php

namespace App\Policies;

use App\Models\RepairJob;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Repair jobs. Staff without "ViewAll:RepairJob" (technicians) only see and
 * update the jobs assigned to them; "ViewFinancials:RepairJob" shows prices.
 */
class RepairJobPolicy extends PermissionPolicy
{
    protected function subject(): string
    {
        return 'RepairJob';
    }

    public function view(User $authUser, Model $record): bool
    {
        return parent::view($authUser, $record) && $this->canSee($authUser, $record);
    }

    public function update(User $authUser, Model $record): bool
    {
        return parent::update($authUser, $record) && $this->canSee($authUser, $record);
    }

    public function viewFinancials(User $authUser): bool
    {
        return $this->allows($authUser, 'ViewFinancials');
    }

    public function viewAll(User $authUser): bool
    {
        return $this->allows($authUser, 'ViewAll');
    }

    /**
     * Limit a jobs query to what this user may see.
     *
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public static function scopeFor(Builder $query, ?User $user): Builder
    {
        if ($user === null || $user->can('viewAll', RepairJob::class)) {
            return $query;
        }

        return $query->where('technician_id', $user->getKey());
    }

    private function canSee(User $user, Model $record): bool
    {
        return $this->viewAll($user) || ($record instanceof RepairJob && $record->technician_id === $user->getKey());
    }
}
