<?php

namespace App\Filament\Resources\Leads\Pages;

use App\Actions\Customers\FindOrCreateCustomer;
use App\Actions\Leads\CaptureLead;
use App\Data\AttributionData;
use App\Enums\Channel;
use App\Filament\Resources\Leads\LeadResource;
use App\Models\Lead;
use App\Models\ServiceCategory;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Staff log an enquiry that came in by phone, WhatsApp or in person.
 * No emails are sent: the customer is already talking to us.
 */
class CreateLead extends CreateRecord
{
    protected static string $resource = LeadResource::class;

    protected static ?string $title = 'Log an enquiry';

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $channel = $data['channel'] instanceof Channel ? $data['channel'] : Channel::from($data['channel']);
        $capture = app(CaptureLead::class);

        $customer = filled($data['name'] ?? null) && (filled($data['email'] ?? null) || filled($data['phone'] ?? null))
            ? app(FindOrCreateCustomer::class)->handle(...$capture->splitName((string) $data['name']), email: $data['email'] ?? null, phone: $data['phone'] ?? null)
            : null;

        return Lead::query()->create([
            ...AttributionData::manual($channel)->toLeadAttributes(),
            ...$data,
            'reference' => $capture->nextReference(),
            'customer_id' => $customer?->getKey(),
            'service_category_id' => ServiceCategory::query()->where('slug', ServiceCategory::IPHONE_REPAIR)->value('id'),
            'event_id' => (string) Str::uuid(),
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return LeadResource::getUrl('view', ['record' => $this->getRecord()]);
    }
}
