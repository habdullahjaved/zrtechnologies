<?php

namespace App\Http\Controllers;

use App\Enums\JobStatus;
use App\Models\RepairJob;
use App\Settings\BusinessSettings;
use App\Support\Phone;
use App\Support\Seo\SeoData;
use Illuminate\Contracts\View\View;

/**
 * /track-repair: the lookup form, and the status page reached by a signed
 * link (from emails, or after a successful lookup).
 */
class TrackRepairController extends Controller
{
    public function index(): View
    {
        return view('pages.track-repair.index', [
            'seo' => new SeoData(
                title: 'Track Your iPhone Repair',
                description: 'Check the progress of your iPhone repair with your booking reference and postcode.',
                image: 'images/og/og-track-repair.jpg',
                imageAlt: 'Track your iPhone repair',
            ),
        ]);
    }

    public function show(string $reference, BusinessSettings $business): View
    {
        $job = RepairJob::query()
            ->where('reference', $reference)
            ->with([
                'deviceModel',
                'items',
                'collectionAddress',
                'statusHistory' => fn ($history) => $history->whereNotNull('customer_message'),
            ])
            ->firstOrFail();

        return view('pages.track-repair.show', [
            'seo' => new SeoData(title: 'Repair '.$job->reference, noindex: true),
            'job' => $job,
            'steps' => JobStatus::trackingSteps(),
            'currentStep' => $job->status->trackingStep(),
            'isStopped' => in_array($job->status, [JobStatus::Cancelled, JobStatus::Unrepairable], true),
            'whatsAppUrl' => Phone::whatsAppUrl($business->whatsapp, "Hi {$business->trading_name}, it's about my repair {$job->reference}."),
        ]);
    }
}
