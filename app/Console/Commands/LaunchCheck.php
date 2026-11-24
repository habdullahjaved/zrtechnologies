<?php

namespace App\Console\Commands;

use App\Models\Page;
use App\Models\RepairPrice;
use App\Models\Review;
use App\Models\ServiceArea;
use App\Settings\SiteSettings;
use App\Settings\SocialSettings;
use App\Settings\TrackingSettings;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Pre-launch checklist. Run on the live server before switching indexing on:
 *
 *     php artisan launch:check
 *
 * Failures (exit code 1) must be fixed; warnings are worth a look. Draft
 * prices and areas fail the check unless --allow-drafts is given.
 */
#[Signature('launch:check {--allow-drafts : Report draft prices and areas as warnings instead of failures}')]
#[Description('Check the site is ready to go live (environment, drafts, legal pages, email, queue, tracking)')]
class LaunchCheck extends Command
{
    /** Cache key the scheduler refreshes every minute (routes/console.php). */
    public const string HEARTBEAT_KEY = 'scheduler:heartbeat';

    /** @var list<array{0: string, 1: string, 2: string}> */
    private array $rows = [];

    private int $failures = 0;

    public function handle(SiteSettings $site, SocialSettings $social, TrackingSettings $tracking): int
    {
        $production = app()->isProduction();
        $draftLevel = $this->option('allow-drafts') ? 'warn' : 'fail';

        $this->check($production, 'fail', 'Environment is production', 'APP_ENV='.app()->environment());
        $this->check(! config('app.debug'), 'fail', 'Debug mode is off', 'Set APP_DEBUG=false');
        $this->check(Str::startsWith((string) config('app.url'), 'https://'), 'fail', 'APP_URL uses HTTPS', (string) config('app.url'));
        $this->check((bool) config('zr.admin.require_mfa'), 'fail', 'Two-factor sign-in is required for staff', 'Set ADMIN_REQUIRE_MFA=true');
        $this->check(! in_array(config('mail.default'), ['log', 'array'], true), 'fail', 'Emails are really sent', 'MAIL_MAILER='.config('mail.default'));
        $this->check(config('queue.default') !== 'sync', 'warn', 'Queued jobs run in the background', 'QUEUE_CONNECTION='.config('queue.default'));
        $this->check(Cache::has(self::HEARTBEAT_KEY), 'warn', 'The scheduler cron is running', 'Add the cron job from docs/DEPLOYMENT.md');
        $this->check(is_link(public_path('storage')) || is_dir(public_path('storage')), 'fail', 'Uploaded files are reachable', 'Run php artisan storage:link');
        $this->check(is_file(public_path('build/manifest.json')), 'fail', 'Front-end assets are built', 'Upload public/build (npm run build)');

        $draftPrices = RepairPrice::query()->where('is_draft', true)->count();
        $this->check($draftPrices === 0, $draftLevel, 'Prices are confirmed', "{$draftPrices} draft prices (Catalogue → Price list)");

        $draftAreas = ServiceArea::query()->where('is_active', true)->where('is_draft', true)->count();
        $this->check($draftAreas === 0, $draftLevel, 'Service areas are confirmed', "{$draftAreas} draft areas (Coverage → Service areas)");

        $legal = Page::query()->where('needs_legal_review', true)->count();
        $this->check($legal === 0, 'fail', 'Legal pages are reviewed', "{$legal} pages marked \"Needs legal review\" (Content → Pages)");

        $this->check($site->allow_indexing, 'warn', 'Search engines may index the site', 'Switch on in Settings → Website & SEO when ready');
        $this->check(! $tracking->test_mode, 'warn', 'Tracking test mode is off', 'Settings → Tracking');
        $this->check(filled($tracking->ga4_measurement_id) || filled($tracking->gtm_container_id), 'warn', 'Analytics is set up', 'Settings → Tracking');
        $this->check(filled($social->google_review_url), 'warn', 'Google review link is set', 'Settings → Social');
        $this->check(! Review::query()->where('is_sample', true)->exists(), 'warn', 'No sample reviews in the database', 'They are never shown live, but can be deleted');

        $this->table(['', 'Check', 'Details'], $this->rows);

        if ($this->failures > 0) {
            $this->error("{$this->failures} ".Str::plural('check', $this->failures).' failed. Fix them before launch.');

            return self::FAILURE;
        }

        $this->info('Ready to launch.');

        return self::SUCCESS;
    }

    /**
     * @param  'fail'|'warn'  $level
     */
    private function check(bool $passed, string $level, string $label, string $detail): void
    {
        if (! $passed && $level === 'fail') {
            $this->failures++;
        }

        $this->rows[] = [
            $passed ? '✓' : ($level === 'fail' ? '✗' : '!'),
            $label,
            $passed ? '' : $detail,
        ];
    }
}
