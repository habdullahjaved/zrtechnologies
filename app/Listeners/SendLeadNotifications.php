<?php

namespace App\Listeners;

use App\Events\LeadCaptured;
use App\Filament\Resources\Leads\LeadResource;
use App\Notifications\EnquiryReceived;
use App\Notifications\StaffAlert;
use App\Settings\NotificationSettings;
use App\Support\Messaging\MessageTemplateCatalogue;
use App\Support\Messaging\MessageVariables;
use Illuminate\Support\Facades\Notification;

/**
 * "We have your message" to the customer and an alert to the team.
 * Unfinished bookings only alert the team, and only if that is switched on.
 */
class SendLeadNotifications
{
    public function __construct(
        private readonly NotificationSettings $settings,
        private readonly MessageVariables $variables,
    ) {}

    public function handle(LeadCaptured $event): void
    {
        $lead = $event->lead;

        if (! $lead->is_partial && filled($lead->email)) {
            Notification::route('mail', $lead->email)->notify(new EnquiryReceived($lead));
        }

        $alertTeam = $lead->is_partial ? $this->settings->notify_partial_bookings : $this->settings->notify_new_enquiries;

        if ($alertTeam) {
            Notification::route('mail', $this->settings->staff_emails)->notify(new StaffAlert(
                MessageTemplateCatalogue::STAFF_NEW_ENQUIRY,
                [...$this->variables->forLead($lead), 'lead_type' => $lead->is_partial ? 'unfinished booking' : mb_strtolower($lead->type->getLabel())],
                LeadResource::getUrl('view', ['record' => $lead], panel: 'admin'),
                'Open lead',
            ));
        }
    }
}
