<?php

namespace App\Policies;

class MenuItemPolicy extends PermissionPolicy
{
    protected function subject(): string
    {
        return 'MenuItem';
    }
}
