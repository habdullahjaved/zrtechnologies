<?php

namespace App\Policies;

class FaqPolicy extends PermissionPolicy
{
    protected function subject(): string
    {
        return 'Faq';
    }
}
