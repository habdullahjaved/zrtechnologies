#!/usr/bin/env bash
# Run every minute from Hostinger's cron (hPanel → Advanced → Cron Jobs).
# Processes queued emails and conversions, publishes scheduled posts and tidies logs.
cd "$(dirname "$0")/.." && php artisan schedule:run >> /dev/null 2>&1
