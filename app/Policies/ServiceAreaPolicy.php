<?php

namespace App\Policies;

class ServiceAreaPolicy extends PermissionPolicy
{
    protected function subject(): string
    {
        return 'ServiceArea';
    }
}
