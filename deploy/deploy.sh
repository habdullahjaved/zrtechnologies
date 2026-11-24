#!/usr/bin/env bash
# Update the live site after pushing new code (see docs/DEPLOYMENT.md).
# Upload public/build (npm run build on your computer) before running this.
set -euo pipefail

cd "$(dirname "$0")/.."

php artisan down --retry=30 || true

git pull --ff-only
composer2 install --no-dev --optimize-autoloader --no-interaction
php artisan migrate --force
php artisan settings:clear-cache
php artisan optimize:clear
php artisan optimize
php artisan cache:clear          # clears cached pages too
php artisan queue:restart

php artisan up
php artisan launch:check --allow-drafts || true
