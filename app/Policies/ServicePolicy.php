<?php

namespace App\Policies;

class ServicePolicy extends PermissionPolicy
{
    protected function subject(): string
    {
        return 'Service';
    }
}
