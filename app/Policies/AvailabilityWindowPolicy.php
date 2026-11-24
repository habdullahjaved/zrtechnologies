<?php

namespace App\Policies;

class AvailabilityWindowPolicy extends PermissionPolicy
{
    protected function subject(): string
    {
        return 'AvailabilityWindow';
    }
}
