<?php

namespace App\Support\Messaging;

use App\Enums\JobStatus;
use App\Enums\MessageChannel;

/**
 * Every message the system sends, with its default wording and the
 * placeholders it may use. The seeder creates a template from each entry
 * (keeping any edits the owner has made); the admin shows the placeholders.
 */
final class MessageTemplateCatalogue
{
    public const string BOOKING_CONFIRMATION = 'booking.confirmation';

    public const string ENQUIRY_RECEIVED = 'enquiry.received';

    public const string STAFF_NEW_BOOKING = 'staff.new_booking';

    public const string STAFF_NEW_ENQUIRY = 'staff.new_enquiry';

    public const string WHATSAPP_LEAD_REPLY = 'whatsapp.lead_reply';

    public const string WHATSAPP_JOB_UPDATE = 'whatsapp.job_update';

    public const string REVIEW_REQUEST = 'review.request';

    public const string WHATSAPP_REVIEW_REQUEST = 'whatsapp.review_request';

    /** Available in every template. */
    public const array COMMON_VARIABLES = [
        'business_name' => 'Trading name, e.g. ZR Technologies',
        'business_phone' => 'Phone number, e.g. 07360 127538',
        'business_email' => 'Contact email',
        'whatsapp_url' => 'WhatsApp chat link',
        'website_url' => 'Website address',
    ];

    public static function jobStatusKey(JobStatus $status): string
    {
        return 'job.status.'.$status->value;
    }

    /**
     * @return array<string, array{channel: MessageChannel, name: string, description: string, subject: string|null, body: string, variables: array<string, string>}>
     */
    public static function all(): array
    {
        $templates = [
            self::BOOKING_CONFIRMATION => [
                'channel' => MessageChannel::Mail,
                'name' => 'Booking confirmation',
                'description' => 'Sent to the customer as soon as they book online.',
                'subject' => 'Booking confirmed: {reference}',
                'body' => <<<'MD'
                    # Thanks, {customer_first_name}. Your repair is booked.

                    Your booking reference is **{reference}**. Keep it handy: you can use it to track your repair.

                    **Device:** {device}

                    **Repairs:**

                    {repairs}

                    **Collection:** {collection_slot}

                    **Collection address:** {collection_address}

                    **Return address:** {return_address}

                    **Estimated total:** {estimate} (includes collection and return: {collection_fee})

                    {payment_note}

                    ## Before we collect

                    {passcode_notice}

                    If you need to change anything, reply to this email or message us on WhatsApp.
                    MD,
                'variables' => [
                    'customer_first_name' => 'Customer’s first name',
                    'reference' => 'Job reference, e.g. ZR-2026-000123',
                    'device' => 'Model, colour and storage',
                    'repairs' => 'Bulleted list of repairs and prices',
                    'collection_slot' => 'Date and time window',
                    'collection_address' => 'Where we collect from',
                    'return_address' => 'Where we return to',
                    'estimate' => 'Estimated total',
                    'collection_fee' => 'Collection and return fee ("Free" when nothing)',
                    'payment_note' => 'Payment note from Booking rules',
                    'passcode_notice' => 'Passcode and Find My guidance from Booking rules',
                    'track_url' => 'Link to track the repair',
                ],
            ],
            self::ENQUIRY_RECEIVED => [
                'channel' => MessageChannel::Mail,
                'name' => 'Enquiry received',
                'description' => 'Sent to the customer after a quote, callback, contact or business enquiry.',
                'subject' => 'We have your message: {reference}',
                'body' => <<<'MD'
                    # Thanks, {customer_first_name}.

                    We have received your {enquiry_type} and a member of the team will be in touch shortly, usually within a few working hours.

                    Your reference is **{reference}**.

                    If it is urgent, call us on {business_phone} or message us on WhatsApp.
                    MD,
                'variables' => [
                    'customer_first_name' => 'Customer’s first name',
                    'reference' => 'Enquiry reference, e.g. ENQ-2026-000045',
                    'enquiry_type' => 'e.g. "quote request" or "callback request"',
                ],
            ],
            self::STAFF_NEW_BOOKING => [
                'channel' => MessageChannel::Staff,
                'name' => 'Team alert: new booking',
                'description' => 'Sent to the team (email, Slack, Telegram) for each new online booking.',
                'subject' => 'New booking {reference}: {device}',
                'body' => <<<'MD'
                    **{customer_name}** booked {reference}.

                    - Device: {device}
                    - Repairs: {repair_names}
                    - Collection: {collection_slot}
                    - Postcode: {postcode} ({area})
                    - Estimate: {estimate}
                    - Phone: {customer_phone}
                    - Email: {customer_email}
                    - Came from: {channel}
                    MD,
                'variables' => [
                    'reference' => 'Job reference',
                    'customer_name' => 'Customer’s name',
                    'customer_phone' => 'Customer’s phone',
                    'customer_email' => 'Customer’s email',
                    'device' => 'Model, colour and storage',
                    'repair_names' => 'Repairs, comma separated',
                    'collection_slot' => 'Date and time window',
                    'postcode' => 'Collection postcode',
                    'area' => 'Service area',
                    'estimate' => 'Estimated total',
                    'channel' => 'Marketing channel, e.g. Google Ads',
                    'admin_url' => 'Link to the job in the admin',
                ],
            ],
            self::STAFF_NEW_ENQUIRY => [
                'channel' => MessageChannel::Staff,
                'name' => 'Team alert: new enquiry',
                'description' => 'Sent to the team for quotes, callbacks, contact and business enquiries, out-of-area requests and unfinished bookings.',
                'subject' => 'New {lead_type}: {customer_name}',
                'body' => <<<'MD'
                    **{customer_name}** sent a {lead_type} ({reference}).

                    - Phone: {customer_phone}
                    - Email: {customer_email}
                    - Postcode: {postcode}
                    - Device: {device}
                    - Came from: {channel}

                    {message}
                    MD,
                'variables' => [
                    'reference' => 'Enquiry reference',
                    'lead_type' => 'e.g. "callback request"',
                    'customer_name' => 'Customer’s name',
                    'customer_phone' => 'Customer’s phone',
                    'customer_email' => 'Customer’s email',
                    'postcode' => 'Postcode',
                    'device' => 'Model',
                    'message' => 'What the customer wrote',
                    'channel' => 'Marketing channel',
                    'admin_url' => 'Link to the lead in the admin',
                ],
            ],
            self::WHATSAPP_LEAD_REPLY => [
                'channel' => MessageChannel::WhatsApp,
                'name' => 'WhatsApp: reply to a lead',
                'description' => 'Prefilled when staff click "WhatsApp" on a lead.',
                'subject' => null,
                'body' => 'Hi {customer_first_name}, it’s {staff_first_name} from {business_name}. Thanks for getting in touch about your {device}. How can we help?',
                'variables' => [
                    'customer_first_name' => 'Customer’s first name',
                    'staff_first_name' => 'Your first name',
                    'device' => 'Model, or "iPhone" if not given',
                    'reference' => 'Enquiry reference',
                ],
            ],
            self::WHATSAPP_JOB_UPDATE => [
                'channel' => MessageChannel::WhatsApp,
                'name' => 'WhatsApp: repair update',
                'description' => 'Prefilled when staff click "WhatsApp" on a repair job.',
                'subject' => null,
                'body' => 'Hi {customer_first_name}, it’s {staff_first_name} from {business_name} with an update on your repair {reference}: {status}. Track it any time here: {track_url}',
                'variables' => [
                    'customer_first_name' => 'Customer’s first name',
                    'staff_first_name' => 'Your first name',
                    'reference' => 'Job reference',
                    'status' => 'Current status, as the customer sees it',
                    'track_url' => 'Link to track the repair',
                ],
            ],
            self::REVIEW_REQUEST => [
                'channel' => MessageChannel::Mail,
                'name' => 'Review request',
                'description' => 'Sent to the customer a few hours after their repair is marked completed, if a Google review link is set. Only ever sent once per repair.',
                'subject' => 'How did we do, {customer_first_name}?',
                'body' => <<<'MD'
                    # Thanks again, {customer_first_name}.

                    We hope your {device} is working perfectly. If you have a moment, we would be very grateful if you could tell others about your experience with a short Google review.

                    It only takes a minute, and it really helps a small local business like ours.

                    If anything wasn’t right, please reply to this email instead so we can put it right.
                    MD,
                'variables' => [
                    'customer_first_name' => 'Customer’s first name',
                    'device' => 'Model',
                    'reference' => 'Job reference',
                    'review_url' => 'Link to leave a Google review',
                ],
            ],
            self::WHATSAPP_REVIEW_REQUEST => [
                'channel' => MessageChannel::WhatsApp,
                'name' => 'WhatsApp: review request',
                'description' => 'Prefilled when staff click "Ask for a review" on a repair job.',
                'subject' => null,
                'body' => 'Hi {customer_first_name}, it’s {staff_first_name} from {business_name}. Thanks for choosing us to repair your {device}. If you are happy with the repair, a quick Google review would really help us: {review_url}',
                'variables' => [
                    'customer_first_name' => 'Customer’s first name',
                    'staff_first_name' => 'Your first name',
                    'device' => 'Model',
                    'review_url' => 'Link to leave a Google review',
                ],
            ],
        ];

        foreach (self::jobStatusTemplates() as $status => $copy) {
            $templates[self::jobStatusKey(JobStatus::from($status))] = [
                'channel' => MessageChannel::Mail,
                'name' => 'Repair update: '.JobStatus::from($status)->getLabel(),
                'description' => 'Sent to the customer when a job moves to "'.JobStatus::from($status)->getLabel().'" (staff can untick "notify").',
                'subject' => $copy['subject'],
                'body' => $copy['body'],
                'variables' => [
                    'customer_first_name' => 'Customer’s first name',
                    'reference' => 'Job reference',
                    'device' => 'Model',
                    'status' => 'Status, as the customer sees it',
                    'status_description' => 'One-line explanation of the status',
                    'message' => 'The message staff typed for the customer (line removed when empty)',
                    'collection_slot' => 'Collection date and time window',
                    'final_price' => 'Final price, if set',
                    'warranty_expires' => 'Warranty end date, if set',
                    'track_url' => 'Link to track the repair',
                ],
            ];
        }

        return $templates;
    }

    /**
     * @return array<string, array{subject: string, body: string}>
     */
    private static function jobStatusTemplates(): array
    {
        return [
            JobStatus::CollectionScheduled->value => [
                'subject' => 'Your collection is booked: {reference}',
                'body' => <<<'MD'
                    # Hi {customer_first_name}, your collection is booked in.

                    We will collect your {device} on **{collection_slot}**.

                    {message}

                    Please back up your iPhone and have it ready. We never need your passcode in advance.
                    MD,
            ],
            JobStatus::Collected->value => [
                'subject' => 'We have your iPhone: {reference}',
                'body' => <<<'MD'
                    # Hi {customer_first_name}, we have your {device}.

                    {status_description}

                    {message}

                    We will be in touch if anything changes. You can track the repair any time.
                    MD,
            ],
            JobStatus::AwaitingApproval->value => [
                'subject' => 'We need your go-ahead: {reference}',
                'body' => <<<'MD'
                    # Hi {customer_first_name}, we need your go-ahead.

                    {status_description}

                    {message}

                    Please reply to this email, call or message us on WhatsApp to approve or decline. We will not go ahead without your agreement.
                    MD,
            ],
            JobStatus::AwaitingParts->value => [
                'subject' => 'Waiting for a part: {reference}',
                'body' => <<<'MD'
                    # Hi {customer_first_name}, we are waiting for a part.

                    {status_description}

                    {message}
                    MD,
            ],
            JobStatus::ReadyForReturn->value => [
                'subject' => 'Your iPhone is ready: {reference}',
                'body' => <<<'MD'
                    # Good news, {customer_first_name}. Your {device} is ready.

                    {status_description}

                    {message}

                    We will be in touch to arrange the return.
                    MD,
            ],
            JobStatus::OutForDelivery->value => [
                'subject' => 'Your iPhone is on its way back: {reference}',
                'body' => <<<'MD'
                    # Hi {customer_first_name}, your {device} is on its way back.

                    {status_description}

                    {message}
                    MD,
            ],
            JobStatus::Returned->value => [
                'subject' => 'Your iPhone is back with you: {reference}',
                'body' => <<<'MD'
                    # Thanks for choosing {business_name}, {customer_first_name}.

                    Your {device} has been returned.

                    {message}

                    If anything isn’t right, get in touch straight away and we will put it right.
                    MD,
            ],
            JobStatus::Completed->value => [
                'subject' => 'Repair complete: {reference}',
                'body' => <<<'MD'
                    # All done, {customer_first_name}.

                    Your repair {reference} is complete. Final price: **{final_price}**.

                    Your warranty runs until **{warranty_expires}**.

                    {message}

                    Thank you for choosing {business_name}.
                    MD,
            ],
            JobStatus::Cancelled->value => [
                'subject' => 'Booking cancelled: {reference}',
                'body' => <<<'MD'
                    # Hi {customer_first_name}, your booking has been cancelled.

                    {message}

                    If this was a mistake or you would like to rebook, just reply to this email or message us on WhatsApp.
                    MD,
            ],
            JobStatus::Unrepairable->value => [
                'subject' => 'An update on your repair: {reference}',
                'body' => <<<'MD'
                    # Hi {customer_first_name}, we are sorry.

                    {status_description}

                    {message}

                    We will be in touch to arrange returning your {device}.
                    MD,
            ],
        ];
    }
}
