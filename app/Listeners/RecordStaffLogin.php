<?php

namespace App\Listeners;

use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Http\Request;

/**
 * Stores when and from where a staff member last signed in.
 */
class RecordStaffLogin
{
    public function __construct(private Request $request) {}

    public function handle(Login $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        $event->user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $this->request->ip(),
        ])->saveQuietly();
    }
}
