<?php

namespace App\Policies;

class ReviewPolicy extends PermissionPolicy
{
    protected function subject(): string
    {
        return 'Review';
    }
}
