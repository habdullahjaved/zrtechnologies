<?php

namespace App\Policies;

class LeadPolicy extends PermissionPolicy
{
    protected function subject(): string
    {
        return 'Lead';
    }
}
