<?php

namespace App\Policies;

class RedirectPolicy extends PermissionPolicy
{
    protected function subject(): string
    {
        return 'Redirect';
    }
}
