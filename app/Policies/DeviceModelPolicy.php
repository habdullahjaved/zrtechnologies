<?php

namespace App\Policies;

class DeviceModelPolicy extends PermissionPolicy
{
    protected function subject(): string
    {
        return 'DeviceModel';
    }
}
