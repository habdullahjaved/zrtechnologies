<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Scheduled tasks
|--------------------------------------------------------------------------
| On Hostinger one cron entry runs `php artisan schedule:run` every minute.
*/

// Keep the audit log for 12 months (config/activitylog.php: clean_after_days).
Schedule::command('activitylog:clean --force')->daily()->at('03:15');

// Process queued jobs (emails, tracking) without a long-running worker.
Schedule::command('queue:work --stop-when-empty --max-time=50 --tries=3')
    ->everyMinute()
    ->withoutOverlapping(5);

// Publish scheduled pages and posts (and refresh cached pages) once their date passes.
Schedule::command('content:publish-scheduled')->everyFiveMinutes()->withoutOverlapping();

// Remove expired password-reset tokens.
Schedule::command('auth:clear-resets')->daily()->at('03:30');
