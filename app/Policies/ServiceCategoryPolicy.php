<?php

namespace App\Policies;

class ServiceCategoryPolicy extends PermissionPolicy
{
    protected function subject(): string
    {
        return 'ServiceCategory';
    }
}
