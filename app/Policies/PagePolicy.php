<?php

namespace App\Policies;

use App\Models\Page;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * System pages (home, legal pages...) can be edited but never deleted.
 */
class PagePolicy extends PermissionPolicy
{
    protected function subject(): string
    {
        return 'Page';
    }

    public function delete(User $authUser, Model $record): bool
    {
        return $record instanceof Page && ! $record->isSystem() && parent::delete($authUser, $record);
    }
}
