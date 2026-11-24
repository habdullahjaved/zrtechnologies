<?php

namespace App\Policies;

class AvailabilityOverridePolicy extends PermissionPolicy
{
    protected function subject(): string
    {
        return 'AvailabilityOverride';
    }
}
