<?php

namespace App\Policies;

class TrackingEventPolicy extends PermissionPolicy
{
    protected function subject(): string
    {
        return 'TrackingEvent';
    }
}
