<?php

namespace App\Policies;

class RepairTypePolicy extends PermissionPolicy
{
    protected function subject(): string
    {
        return 'RepairType';
    }
}
