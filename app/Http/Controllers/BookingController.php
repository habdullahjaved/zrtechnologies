<?php

namespace App\Http\Controllers;

use App\Models\RepairJob;
use App\Settings\BookingSettings;
use App\Settings\BusinessSettings;
use App\Support\Messaging\MessageVariables;
use App\Support\Phone;
use App\Support\Seo\SeoData;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;

/**
 * /book-repair: the booking wizard, its thank-you page and calendar file.
 * The thank-you and calendar links are signed, so only the customer who
 * booked (or someone they share the link with) can open them.
 */
class BookingController extends Controller
{
    public function create(): View
    {
        return view('pages.booking.create', [
            'seo' => new SeoData(
                title: 'Book an iPhone Repair with Collection',
                description: 'Book your iPhone repair online in a few minutes. We collect from your home or workplace, repair it and bring it back.',
                image: 'images/og/og-book-repair.jpg',
                imageAlt: 'Book an iPhone repair with ZR Technologies',
            ),
        ]);
    }

    public function thanks(Request $request, BookingSettings $settings, BusinessSettings $business): View
    {
        $job = $this->job((string) $request->query('ref'));

        return view('pages.booking.thanks', [
            'seo' => new SeoData(title: 'Booking confirmed', noindex: true),
            'job' => $job,
            'paymentNote' => $settings->payment_note,
            'passcodeNotice' => $settings->passcode_notice,
            'trackUrl' => MessageVariables::trackUrl($job),
            'calendarUrl' => URL::signedRoute('booking.calendar', ['reference' => $job->reference]),
            'googleCalendarUrl' => $this->googleCalendarUrl($job, $business),
            'whatsAppUrl' => Phone::whatsAppUrl($business->whatsapp, "Hi {$business->trading_name}, it's about my booking {$job->reference}."),
        ]);
    }

    /**
     * An .ics file so the customer can add the collection to any calendar.
     */
    public function calendar(string $reference, BusinessSettings $business): Response
    {
        $job = $this->job($reference);
        [$starts, $ends] = $this->collectionTimes($job);

        abort_if($starts === null || $ends === null, 404);

        $escape = fn (string $text): string => addcslashes($text, ',;\\');

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//'.$business->trading_name.'//Bookings//EN',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'BEGIN:VEVENT',
            'UID:'.$job->reference.'@'.parse_url((string) config('app.url'), PHP_URL_HOST),
            'DTSTAMP:'.now()->utc()->format('Ymd\THis\Z'),
            'DTSTART:'.$starts->utc()->format('Ymd\THis\Z'),
            'DTEND:'.$ends->utc()->format('Ymd\THis\Z'),
            'SUMMARY:'.$escape("iPhone collection ({$job->reference})"),
            'DESCRIPTION:'.$escape("{$business->trading_name} will collect your iPhone. Please back it up before we arrive. Track your repair: ".MessageVariables::trackUrl($job)),
            'LOCATION:'.$escape((string) $job->collectionAddress?->oneLine()),
            'END:VEVENT',
            'END:VCALENDAR',
        ];

        return response(implode("\r\n", $lines)."\r\n", 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="'.$job->reference.'.ics"',
            'X-Robots-Tag' => 'noindex',
        ]);
    }

    private function job(string $reference): RepairJob
    {
        return RepairJob::query()
            ->where('reference', $reference)
            ->with(['customer', 'deviceModel', 'items', 'collectionAddress', 'returnAddress', 'availabilityWindow'])
            ->firstOrFail();
    }

    /**
     * @return array{0: Carbon|null, 1: Carbon|null}
     */
    private function collectionTimes(RepairJob $job): array
    {
        if ($job->collection_date === null || $job->availabilityWindow === null) {
            return [null, null];
        }

        $date = $job->collection_date->copy();

        return [
            $date->copy()->setTimeFromTimeString($job->availabilityWindow->starts_at),
            $date->copy()->setTimeFromTimeString($job->availabilityWindow->ends_at),
        ];
    }

    private function googleCalendarUrl(RepairJob $job, BusinessSettings $business): ?string
    {
        [$starts, $ends] = $this->collectionTimes($job);

        if ($starts === null || $ends === null) {
            return null;
        }

        return 'https://calendar.google.com/calendar/render?'.http_build_query([
            'action' => 'TEMPLATE',
            'text' => "iPhone collection ({$job->reference})",
            'dates' => $starts->utc()->format('Ymd\THis\Z').'/'.$ends->utc()->format('Ymd\THis\Z'),
            'details' => "{$business->trading_name} will collect your iPhone. Please back it up before we arrive.",
            'location' => (string) $job->collectionAddress?->oneLine(),
        ]);
    }
}
