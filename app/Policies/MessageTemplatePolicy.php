<?php

namespace App\Policies;

class MessageTemplatePolicy extends PermissionPolicy
{
    protected function subject(): string
    {
        return 'MessageTemplate';
    }
}
