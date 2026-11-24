<?php

namespace App\Policies;

class PostCategoryPolicy extends PermissionPolicy
{
    protected function subject(): string
    {
        return 'PostCategory';
    }
}
