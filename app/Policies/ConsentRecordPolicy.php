<?php

namespace App\Policies;

class ConsentRecordPolicy extends PermissionPolicy
{
    protected function subject(): string
    {
        return 'ConsentRecord';
    }
}
