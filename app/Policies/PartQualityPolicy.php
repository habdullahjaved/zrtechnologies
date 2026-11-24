<?php

namespace App\Policies;

class PartQualityPolicy extends PermissionPolicy
{
    protected function subject(): string
    {
        return 'PartQuality';
    }
}
