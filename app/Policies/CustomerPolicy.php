<?php

namespace App\Policies;

class CustomerPolicy extends PermissionPolicy
{
    protected function subject(): string
    {
        return 'Customer';
    }
}
