<?php

namespace App\Policies;

class BlackoutDatePolicy extends PermissionPolicy
{
    protected function subject(): string
    {
        return 'BlackoutDate';
    }
}
