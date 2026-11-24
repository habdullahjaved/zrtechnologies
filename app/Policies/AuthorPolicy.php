<?php

namespace App\Policies;

class AuthorPolicy extends PermissionPolicy
{
    protected function subject(): string
    {
        return 'Author';
    }
}
