<?php

namespace App\Policies;

class PostPolicy extends PermissionPolicy
{
    protected function subject(): string
    {
        return 'Post';
    }
}
