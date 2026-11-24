<?php

namespace App\Policies;

class VideoPolicy extends PermissionPolicy
{
    protected function subject(): string
    {
        return 'Video';
    }
}
