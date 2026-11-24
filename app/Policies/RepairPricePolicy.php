<?php

namespace App\Policies;

class RepairPricePolicy extends PermissionPolicy
{
    protected function subject(): string
    {
        return 'RepairPrice';
    }
}
