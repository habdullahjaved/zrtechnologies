<?php

namespace Database\Seeders;

use App\Enums\Channel;
use App\Enums\JobStatus;
use App\Enums\LeadStatus;
use App\Enums\LeadType;
use App\Models\AvailabilityWindow;
use App\Models\Customer;
use App\Models\DeviceModel;
use App\Models\JobStatusChange;
use App\Models\Lead;
use App\Models\RepairJob;
use App\Models\RepairPrice;
use App\Models\ServiceArea;
use App\Models\User;
use App\Support\ReferenceGenerator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Sample leads, customers and repair jobs so the CRM screens can be reviewed
 * locally. Every name starts with "Demo" and every email is @example.com.
 * Never run in production (see DatabaseSeeder); runs once (skipped if demo
 * customers already exist).
 */
class DemoCrmSeeder extends Seeder
{
    /**
     * @var list<array{first: string, last: string, postcode: string, model: string, repairs: list<string>, status: JobStatus, channel: Channel, days: int}>
     */
    private const array JOBS = [
        ['first' => 'Demo Sarah', 'last' => 'Ahmed', 'postcode' => 'BB2 4NY', 'model' => 'iphone-15', 'repairs' => ['screen-replacement'], 'status' => JobStatus::Booked, 'channel' => Channel::GoogleAds, 'days' => 1],
        ['first' => 'Demo Tom', 'last' => 'Hughes', 'postcode' => 'BB3 1AA', 'model' => 'iphone-13', 'repairs' => ['battery-replacement'], 'status' => JobStatus::Collected, 'channel' => Channel::GoogleBusinessProfile, 'days' => -1],
        ['first' => 'Demo Priya', 'last' => 'Patel', 'postcode' => 'BB5 1AA', 'model' => 'iphone-14-pro', 'repairs' => ['screen-replacement', 'back-glass-repair'], 'status' => JobStatus::AwaitingParts, 'channel' => Channel::MetaAds, 'days' => -3],
        ['first' => 'Demo James', 'last' => 'Walsh', 'postcode' => 'BB1 5AA', 'model' => 'iphone-12', 'repairs' => ['charging-port-repair'], 'status' => JobStatus::ReadyForReturn, 'channel' => Channel::GoogleOrganic, 'days' => -4],
        ['first' => 'Demo Aisha', 'last' => 'Khan', 'postcode' => 'BB7 1AA', 'model' => 'iphone-16', 'repairs' => ['battery-replacement'], 'status' => JobStatus::Completed, 'channel' => Channel::Direct, 'days' => -10],
    ];

    public function run(ReferenceGenerator $references): void
    {
        if (Customer::query()->where('first_name', 'like', 'Demo %')->exists()) {
            return;
        }

        $technician = User::query()->whereHas('roles', fn ($roles) => $roles->where('name', 'technician'))->first();
        $window = AvailabilityWindow::query()->where('is_active', true)->orderBy('weekday')->orderBy('starts_at')->first();

        foreach (self::JOBS as $index => $demo) {
            $this->job($demo, $index, $references, $technician, $window);
        }

        $this->openLeads($references);
    }

    /**
     * @param  array{first: string, last: string, postcode: string, model: string, repairs: list<string>, status: JobStatus, channel: Channel, days: int}  $demo
     */
    private function job(array $demo, int $index, ReferenceGenerator $references, ?User $technician, ?AvailabilityWindow $window): void
    {
        $model = DeviceModel::query()->where('slug', $demo['model'])->first();

        if ($model === null) {
            return;
        }

        $customer = Customer::query()->create([
            'first_name' => $demo['first'],
            'last_name' => $demo['last'],
            'email' => Str::slug($demo['first'].' '.$demo['last'], '.').'@example.com',
            'phone' => '+44791234'.str_pad((string) (5670 + $index), 4, '0', STR_PAD_LEFT),
        ]);

        $address = $customer->addresses()->create([
            'line1' => (10 + $index).' Demo Street',
            'town' => ServiceArea::query()->whereJsonContains('postcode_districts', explode(' ', $demo['postcode'])[0])->value('town') ?? 'Blackburn',
            'postcode' => $demo['postcode'],
            'is_default' => true,
        ]);

        $prices = RepairPrice::query()
            ->with(['repairType', 'partQuality'])
            ->where('device_model_id', $model->id)
            ->whereHas('repairType', fn ($repair) => $repair->whereIn('slug', $demo['repairs']))
            ->orderBy('part_quality_id')
            ->get()
            ->unique('repair_type_id');

        $repairsPence = $prices->sum(fn (RepairPrice $price): int => $price->effectivePricePence());
        $area = ServiceArea::query()->whereJsonContains('postcode_districts', explode(' ', $demo['postcode'])[0])->first();
        $bookedAt = now()->addDays(min(0, $demo['days']))->subDays(1)->setTime(10, 15);

        $lead = Lead::query()->create([
            'reference' => $references->next(ReferenceGenerator::LEAD, 'ENQ'),
            'type' => LeadType::Booking,
            'status' => match ($demo['status']) {
                JobStatus::Completed => LeadStatus::Completed,
                JobStatus::Booked => LeadStatus::Booked,
                default => LeadStatus::Collected,
            },
            'customer_id' => $customer->id,
            'name' => $customer->full_name,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'postcode' => $demo['postcode'],
            'device_model_id' => $model->id,
            'repair_type_id' => $prices->first()?->repair_type_id,
            'service_area_id' => $area?->id,
            'estimated_value_pence' => $repairsPence + ($area->collection_fee_pence ?? 0),
            'channel' => $demo['channel'],
            'first_touch_channel' => $demo['channel'],
            'gclid' => $demo['channel'] === Channel::GoogleAds ? 'DEMO-GCLID-'.$index : null,
            'utm_source' => match ($demo['channel']) {
                Channel::GoogleAds, Channel::GoogleBusinessProfile => 'google',
                Channel::MetaAds => 'facebook',
                default => null,
            },
            'utm_medium' => match ($demo['channel']) {
                Channel::GoogleAds => 'cpc',
                Channel::GoogleBusinessProfile => 'organic',
                Channel::MetaAds => 'paid_social',
                default => null,
            },
            'utm_campaign' => $demo['channel'] === Channel::GoogleBusinessProfile ? 'gbp' : null,
            'landing_page' => '/',
            'device_type' => 'mobile',
            'event_id' => (string) Str::uuid(),
            'converted_at' => $bookedAt,
            'created_at' => $bookedAt,
        ]);

        $job = RepairJob::query()->create([
            'reference' => $references->next(ReferenceGenerator::JOB, 'ZR'),
            'status' => $demo['status'],
            'lead_id' => $lead->id,
            'customer_id' => $customer->id,
            'technician_id' => $demo['status'] === JobStatus::Booked ? null : $technician?->id,
            'service_area_id' => $area?->id,
            'device_model_id' => $model->id,
            'device_colour' => 'Black',
            'reported_fault' => $prices->map(fn (RepairPrice $price): string => $price->repairType->name)->implode(', '),
            'collection_address_id' => $address->id,
            'collection_date' => now()->addDays($demo['days'])->toDateString(),
            'availability_window_id' => $window?->id,
            'collection_window' => $window?->timeRange(),
            'quoted_price_pence' => $repairsPence,
            'collection_fee_pence' => $area->collection_fee_pence ?? 0,
            'final_price_pence' => $demo['status'] === JobStatus::Completed ? $repairsPence + ($area->collection_fee_pence ?? 0) : null,
            'terms_accepted_at' => $bookedAt,
            'passcode_policy_accepted_at' => $bookedAt,
            'start_within_cancellation_period' => true,
            'booked_at' => $bookedAt,
            'collected_at' => $demo['status'] === JobStatus::Booked ? null : $bookedAt->copy()->addDay(),
            'completed_at' => $demo['status'] === JobStatus::Completed ? $bookedAt->copy()->addDays(3) : null,
            'paid_at' => $demo['status'] === JobStatus::Completed ? $bookedAt->copy()->addDays(3) : null,
            'warranty_expires_at' => $demo['status'] === JobStatus::Completed ? $bookedAt->copy()->addDays(3 + (int) $prices->max('warranty_days'))->toDateString() : null,
        ]);

        foreach ($prices->values() as $sort => $price) {
            $job->items()->create([
                'repair_type_id' => $price->repair_type_id,
                'part_quality_id' => $price->part_quality_id,
                'description' => $price->repairType->name.($price->partQuality ? ' ('.$price->partQuality->name.')' : ''),
                'price_pence' => $price->effectivePricePence(),
                'warranty_days' => $price->warranty_days,
                'sort' => $sort,
            ]);
        }

        JobStatusChange::query()->create(['repair_job_id' => $job->id, 'to_status' => JobStatus::Booked, 'note' => 'Booked online.', 'customer_notified' => true, 'created_at' => $bookedAt]);

        if ($demo['status'] !== JobStatus::Booked) {
            JobStatusChange::query()->create(['repair_job_id' => $job->id, 'from_status' => JobStatus::Booked, 'to_status' => $demo['status'], 'note' => 'Demo status.', 'customer_message' => $demo['status'] === JobStatus::AwaitingParts ? 'Your new back glass arrives tomorrow.' : null, 'user_id' => $technician?->id]);
        }
    }

    private function openLeads(ReferenceGenerator $references): void
    {
        $leads = [
            ['type' => LeadType::Callback, 'name' => 'Demo Chloe Barnes', 'phone' => '+447912345690', 'message' => null, 'details' => ['callback_time' => 'Morning (9am–12pm)'], 'channel' => Channel::GoogleOrganic],
            ['type' => LeadType::Quote, 'name' => 'Demo Daniel Price', 'phone' => '+447912345691', 'message' => 'Face ID stopped working after a drop. How much to fix?', 'details' => [], 'channel' => Channel::MetaOrganic],
            ['type' => LeadType::Business, 'name' => 'Demo Laura Finch', 'phone' => null, 'message' => 'We have 12 staff iPhones with worn batteries. Can you collect from our office?', 'details' => ['fleet_size' => '6 to 20 devices'], 'channel' => Channel::Referral],
            ['type' => LeadType::OutOfArea, 'name' => 'Demo Ryan Cole', 'phone' => '+447912345693', 'message' => 'Cracked screen, I am in Bolton.', 'details' => [], 'channel' => Channel::Direct],
        ];

        foreach ($leads as $index => $demo) {
            Lead::query()->create([
                'reference' => $references->next(ReferenceGenerator::LEAD, 'ENQ'),
                'type' => $demo['type'],
                'status' => LeadStatus::New,
                'name' => $demo['name'],
                'email' => Str::slug($demo['name'], '.').'@example.com',
                'phone' => $demo['phone'],
                'company_name' => $demo['type'] === LeadType::Business ? 'Demo Office Supplies Ltd' : null,
                'message' => $demo['message'],
                'details' => $demo['details'] ?: null,
                'channel' => $demo['channel'],
                'first_touch_channel' => $demo['channel'],
                'landing_page' => '/contact',
                'device_type' => 'mobile',
                'follow_up_at' => $index === 0 ? now()->subHour() : null,
                'event_id' => (string) Str::uuid(),
            ]);
        }

        Lead::query()->create([
            'reference' => $references->next(ReferenceGenerator::LEAD, 'ENQ'),
            'type' => LeadType::Booking,
            'status' => LeadStatus::New,
            'is_partial' => true,
            'phone' => '+447912345699',
            'postcode' => 'BB2 6AA',
            'device_model_id' => DeviceModel::query()->where('slug', 'iphone-15-pro')->value('id'),
            'details' => ['step' => 'Collection', 'repairs' => ['Screen Replacement (Premium)'], 'slot' => null],
            'channel' => Channel::GoogleAds,
            'first_touch_channel' => Channel::GoogleAds,
            'gclid' => 'DEMO-GCLID-PARTIAL',
            'utm_source' => 'google',
            'utm_medium' => 'cpc',
            'landing_page' => '/iphone-repair',
            'device_type' => 'mobile',
            'event_id' => (string) Str::uuid(),
        ]);
    }
}
