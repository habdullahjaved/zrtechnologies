<?php

namespace App\Support\Messaging;

use App\Models\JobStatusChange;
use App\Models\Lead;
use App\Models\RepairJob;
use App\Models\RepairJobItem;
use App\Settings\BookingSettings;
use App\Support\Money;
use App\Support\Phone;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\HtmlString;

/**
 * The values behind each template's {placeholders}.
 */
final class MessageVariables
{
    public function __construct(private readonly BookingSettings $booking) {}

    /**
     * A permanent signed link straight to a job's tracking page.
     */
    public static function trackUrl(RepairJob $job): string
    {
        return URL::signedRoute('track-repair.show', ['reference' => $job->reference]);
    }

    /**
     * @return array<string, string|HtmlString|null>
     */
    public function forJob(RepairJob $job): array
    {
        $job->loadMissing(['customer', 'deviceModel', 'items', 'collectionAddress', 'returnAddress', 'serviceArea', 'lead']);

        $repairs = $job->items
            ->map(fn (RepairJobItem $item): string => '- '.e($item->description).': '.$item->formattedPrice())
            ->implode("\n");

        return [
            'customer_first_name' => $job->customer->first_name,
            'customer_name' => $job->customer->full_name,
            'customer_phone' => $job->customer->phone ? Phone::display($job->customer->phone) : '',
            'customer_email' => $job->customer->email,
            'reference' => $job->reference,
            'device' => $this->device($job),
            'repairs' => new HtmlString($repairs),
            'repair_names' => $job->items->pluck('description')->implode(', '),
            'collection_slot' => $job->collectionSlotLabel(),
            'collection_address' => $job->collectionAddress?->oneLine(),
            'return_address' => $job->returnAddress?->oneLine() ?? 'Same as collection address',
            'postcode' => $job->collectionAddress?->postcode,
            'area' => $job->serviceArea?->name,
            'estimate' => $job->estimatedTotalLabel(),
            'collection_fee' => $job->collection_fee_pence > 0 ? Money::format($job->collection_fee_pence) : 'Free',
            'final_price' => $job->final_price_pence !== null ? Money::format($job->final_price_pence) : null,
            'warranty_expires' => $job->warranty_expires_at?->format('j F Y'),
            'status' => $job->status->customerLabel(),
            'status_description' => $job->status->customerDescription(),
            'payment_note' => $this->booking->payment_note,
            'passcode_notice' => $this->booking->passcode_notice,
            'channel' => $job->lead?->channel->getLabel(),
            'track_url' => self::trackUrl($job),
        ];
    }

    /**
     * @return array<string, string|HtmlString|null>
     */
    public function forStatusChange(RepairJob $job, JobStatusChange $change): array
    {
        return [
            ...$this->forJob($job),
            'status' => $change->to_status->customerLabel(),
            'status_description' => $change->to_status->customerDescription(),
            'message' => $change->customer_message,
        ];
    }

    /**
     * @return array<string, string|HtmlString|null>
     */
    public function forLead(Lead $lead): array
    {
        $lead->loadMissing(['deviceModel']);

        return [
            'customer_first_name' => $lead->firstName(),
            'customer_name' => $lead->displayName(),
            'customer_phone' => $lead->phone ? Phone::display($lead->phone) : '',
            'customer_email' => $lead->email,
            'reference' => $lead->reference,
            'lead_type' => mb_strtolower($lead->type->getLabel()),
            'enquiry_type' => mb_strtolower($lead->type->getLabel()),
            'postcode' => $lead->postcode,
            'device' => $lead->deviceModel->name ?? 'iPhone',
            'message' => $lead->message,
            'channel' => $lead->channel->getLabel(),
        ];
    }

    private function device(RepairJob $job): string
    {
        return collect([$job->deviceModel->name ?? 'iPhone', $job->device_colour, $job->device_storage])->filter()->implode(', ');
    }
}
