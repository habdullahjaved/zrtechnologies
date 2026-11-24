<?php

namespace App\Policies;

class NotFoundLogPolicy extends PermissionPolicy
{
    protected function subject(): string
    {
        return 'NotFoundLog';
    }
}
