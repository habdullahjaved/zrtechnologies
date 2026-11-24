<?php

use App\Enums\Channel;
use App\Enums\JobStatus;
use App\Enums\LeadStatus;
use App\Enums\LeadType;
use App\Enums\StaffRole;
use App\Filament\Pages\Settings\ManageNotificationSettings;
use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\Customers\Pages\EditCustomer;
use App\Filament\Resources\Leads\LeadResource;
use App\Filament\Resources\Leads\Pages\CreateLead;
use App\Filament\Resources\Leads\Pages\ListLeads;
use App\Filament\Resources\Leads\Pages\ViewLead;
use App\Filament\Resources\MessageTemplates\MessageTemplateResource;
use App\Filament\Resources\MessageTemplates\Pages\EditMessageTemplate;
use App\Filament\Resources\RepairJobs\Pages\EditRepairJob;
use App\Filament\Resources\RepairJobs\Pages\ListRepairJobs;
use App\Filament\Resources\RepairJobs\Pages\ViewRepairJob;
use App\Filament\Resources\RepairJobs\RepairJobResource;
use App\Models\Customer;
use App\Models\DeviceModel;
use App\Models\Lead;
use App\Models\MessageTemplate;
use App\Models\Note;
use App\Models\RepairJob;
use App\Models\User;
use App\Settings\NotificationSettings;
use App\Support\Messaging\MessageTemplateCatalogue;
use Database\Seeders\MessageTemplateSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Notification;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    config(['zr.admin.require_mfa' => false]);
    Notification::fake();
});

it('gives each role the right CRM and workshop screens', function (StaffRole $role, bool $leads, bool $customers, bool $jobs, bool $templates, bool $alerts) {
    $this->actingAs(User::factory()->withRole($role)->create());

    $this->get(LeadResource::getUrl('index'))->assertStatus($leads ? 200 : 403);
    $this->get(CustomerResource::getUrl('index'))->assertStatus($customers ? 200 : 403);
    $this->get(RepairJobResource::getUrl('index'))->assertStatus($jobs ? 200 : 403);
    $this->get(MessageTemplateResource::getUrl('index'))->assertStatus($templates ? 200 : 403);
    $this->get(ManageNotificationSettings::getUrl())->assertStatus($alerts ? 200 : 403);
})->with([
    'super admin' => [StaffRole::SuperAdmin, true, true, true, true, true],
    'manager' => [StaffRole::Manager, true, true, true, true, true],
    'technician' => [StaffRole::Technician, false, false, true, false, false],
    'content editor' => [StaffRole::ContentEditor, false, false, false, true, false],
    'sales/support' => [StaffRole::SalesSupport, true, true, true, false, false],
]);

it('shows technicians only their own jobs, without prices', function () {
    $technician = User::factory()->withRole(StaffRole::Technician)->create();
    $mine = RepairJob::factory()->create(['technician_id' => $technician->id]);
    $someoneElses = RepairJob::factory()->create();

    $this->actingAs($technician);

    livewire(ListRepairJobs::class)
        ->assertCanSeeTableRecords([$mine])
        ->assertCanNotSeeTableRecords([$someoneElses])
        ->assertTableColumnHidden('estimated_total');

    $this->get(RepairJobResource::getUrl('view', ['record' => $mine]))->assertOk()->assertDontSee('Repairs and price');
    $this->get(RepairJobResource::getUrl('view', ['record' => $someoneElses]))->assertNotFound();
    expect($technician->can('delete', $mine))->toBeFalse();
});

it('lets office staff see every job with prices', function () {
    $this->actingAs(User::factory()->withRole(StaffRole::SalesSupport)->create());
    $jobs = RepairJob::factory()->count(2)->create();

    livewire(ListRepairJobs::class)
        ->assertCanSeeTableRecords($jobs)
        ->assertTableColumnVisible('estimated_total');
});

it('lists open leads first and shows a lead with its attribution', function () {
    $this->actingAs(User::factory()->superAdmin()->create());
    $open = Lead::factory()->fromGoogleAds()->create(['name' => 'Sarah Ahmed']);
    $lost = Lead::factory()->status(LeadStatus::Lost)->create();

    livewire(ListLeads::class)
        ->assertCanSeeTableRecords([$open])
        ->assertCanNotSeeTableRecords([$lost]);

    $this->get(LeadResource::getUrl('view', ['record' => $open]))
        ->assertOk()
        ->assertSee('Sarah Ahmed')
        ->assertSee($open->gclid)
        ->assertSee('Google Ads');
});

it('finds leads, customers and jobs by phone number however it is typed', function () {
    $customer = Customer::factory()->create(['phone' => '+447912345678']);

    expect(CustomerResource::getGlobalSearchResults('07912 345678')->count())->toBe(0); // not signed in

    $this->actingAs(User::factory()->superAdmin()->create());

    expect(CustomerResource::getGlobalSearchResults('07912 345678')->first()?->title)->toBe($customer->full_name);
});

it('finds a job by IMEI although it is stored encrypted', function () {
    $this->actingAs(User::factory()->superAdmin()->create());
    $job = RepairJob::factory()->create(['imei' => '35 209900 176148 1']);

    expect($job->getRawOriginal('imei'))->not->toContain('352099')
        ->and($job->fresh()->imei)->toBe('35 209900 176148 1');

    $results = RepairJobResource::getGlobalSearchResults('352099001761481');

    expect($results)->toHaveCount(1)
        ->and($results->first()->title)->toStartWith($job->reference);
});

it('logs a phone enquiry and links it to the customer', function () {
    $this->actingAs($user = User::factory()->withRole(StaffRole::SalesSupport)->create());

    livewire(CreateLead::class)
        ->fillForm([
            'type' => LeadType::CallClick->value,
            'channel' => Channel::Phone->value,
            'name' => 'Tom Hughes',
            'phone' => '07912 345678',
            'message' => 'Wants a battery quote',
            'status' => LeadStatus::Contacted->value,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $lead = Lead::query()->sole();

    expect($lead->reference)->toStartWith('ENQ-')
        ->and($lead->channel)->toBe(Channel::Phone)
        ->and($lead->assignee->is($user))->toBeTrue()
        ->and($lead->customer->phone)->toBe('+447912345678');

    Notification::assertNothingSent();
});

it('updates a lead’s status and requires a reason when it is lost', function () {
    $this->actingAs(User::factory()->withRole(StaffRole::SalesSupport)->create());
    $lead = Lead::factory()->create();

    livewire(ViewLead::class, ['record' => $lead->getRouteKey()])
        ->callAction('changeStatus', ['status' => LeadStatus::Lost->value, 'lost_reason' => ''])
        ->assertHasActionErrors(['lost_reason' => 'required']);

    livewire(ViewLead::class, ['record' => $lead->getRouteKey()])
        ->callAction('changeStatus', ['status' => LeadStatus::Lost->value, 'lost_reason' => 'Went elsewhere'])
        ->assertHasNoActionErrors();

    expect($lead->fresh()->status)->toBe(LeadStatus::Lost)
        ->and($lead->fresh()->lost_reason)->toBe('Went elsewhere')
        ->and($lead->fresh()->contacted_at)->not->toBeNull();
});

it('converts a lead into a repair job', function () {
    $this->actingAs($user = User::factory()->withRole(StaffRole::SalesSupport)->create());
    $model = DeviceModel::factory()->create();
    $lead = Lead::factory()->type(LeadType::Quote)->create(['name' => 'Priya Patel', 'phone' => '+447912345678', 'device_model_id' => $model->id]);

    livewire(ViewLead::class, ['record' => $lead->getRouteKey()])
        ->callAction('convertToJob', [
            'device_model_id' => $model->id,
            'reported_fault' => 'Cracked screen',
            'quoted_price_pence' => '89.00',
            'line1' => '1 Church Street',
            'town' => 'Darwen',
            'postcode' => 'bb3 1aa',
        ])
        ->assertHasNoActionErrors();

    $job = RepairJob::query()->sole();

    expect($job->reference)->toMatch('/^ZR-\d{4}-\d{6}$/')
        ->and($job->quoted_price_pence)->toBe(8900)
        ->and($job->customer->full_name)->toBe('Priya Patel')
        ->and($job->collectionAddress->postcode)->toBe('BB3 1AA')
        ->and($job->statusHistory()->sole()->user->is($user))->toBeTrue()
        ->and($lead->fresh()->status)->toBe(LeadStatus::Booked)
        ->and($lead->fresh()->converted_at)->not->toBeNull();
});

it('moves a job on from its page and records the change', function () {
    $this->seed(MessageTemplateSeeder::class);
    $this->actingAs($user = User::factory()->withRole(StaffRole::Manager)->create());
    $job = RepairJob::factory()->create();

    livewire(ViewRepairJob::class, ['record' => $job->getRouteKey()])
        ->callAction('changeStatus', ['status' => JobStatus::Repairing->value, 'notify' => false])
        ->assertHasActionErrors(['status']);

    livewire(ViewRepairJob::class, ['record' => $job->getRouteKey()])
        ->callAction('changeStatus', ['status' => JobStatus::Collected->value, 'customer_message' => 'Collected safely.', 'notify' => true])
        ->assertHasNoActionErrors();

    $change = $job->statusHistory()->first();

    expect($job->fresh()->status)->toBe(JobStatus::Collected)
        ->and($change->user->is($user))->toBeTrue()
        ->and($change->customer_message)->toBe('Collected safely.')
        ->and($change->customer_notified)->toBeTrue();
});

it('lets technicians record the diagnosis and IMEI but not change the customer or price', function () {
    $technician = User::factory()->withRole(StaffRole::Technician)->create();
    $job = RepairJob::factory()->create(['technician_id' => $technician->id, 'quoted_price_pence' => 8900]);

    $this->actingAs($technician);

    livewire(EditRepairJob::class, ['record' => $job->getRouteKey()])
        ->assertFormFieldHidden('customer_id')
        ->assertFormFieldHidden('final_price_pence')
        ->fillForm(['diagnosis' => 'Display connector damaged', 'imei' => '352099001761481'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($job->fresh()->diagnosis)->toBe('Display connector damaged')
        ->and($job->fresh()->quoted_price_pence)->toBe(8900);
});

it('warns about a possible duplicate customer', function () {
    $this->actingAs(User::factory()->superAdmin()->create());
    Customer::factory()->create(['first_name' => 'Sarah', 'last_name' => 'Ahmed', 'email' => 'sarah@example.com']);
    $other = Customer::factory()->create();

    livewire(EditCustomer::class, ['record' => $other->getRouteKey()])
        ->fillForm(['email' => 'sarah@example.com'])
        ->assertSee('Already used by');
});

it('keeps notes on a lead, editable only by their author', function () {
    $this->actingAs($author = User::factory()->superAdmin()->create());
    $lead = Lead::factory()->create();
    $note = Note::factory()->create(['notable_type' => $lead->getMorphClass(), 'notable_id' => $lead->id, 'user_id' => $author->id, 'body' => 'Called, no answer']);

    $this->get(LeadResource::getUrl('view', ['record' => $lead]))->assertOk();

    expect($lead->notes()->sole()->is($note))->toBeTrue();
});

it('lets the owner reword a template and restore the original', function () {
    $this->seed(MessageTemplateSeeder::class);
    $this->actingAs(User::factory()->withRole(StaffRole::ContentEditor)->create());
    $template = MessageTemplate::query()->where('key', MessageTemplateCatalogue::ENQUIRY_RECEIVED)->sole();

    livewire(EditMessageTemplate::class, ['record' => $template->getRouteKey()])
        ->assertSee('{customer_first_name}')
        ->fillForm(['subject' => 'Thanks for your message ({reference})'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($template->fresh()->subject)->toBe('Thanks for your message ({reference})');

    livewire(EditMessageTemplate::class, ['record' => $template->getRouteKey()])->callAction('restoreDefault');

    expect($template->fresh()->subject)->toBe(MessageTemplateCatalogue::all()[MessageTemplateCatalogue::ENQUIRY_RECEIVED]['subject']);
});

it('saves the team alert settings', function () {
    $this->actingAs(User::factory()->withRole(StaffRole::Manager)->create());

    livewire(ManageNotificationSettings::class)
        ->fillForm(['staff_emails' => ['team@example.com', 'owner@example.com'], 'notify_partial_bookings' => true])
        ->call('save')
        ->assertHasNoFormErrors();

    $settings = freshSettings(NotificationSettings::class);

    expect($settings->staff_emails)->toBe(['team@example.com', 'owner@example.com'])
        ->and($settings->notify_partial_bookings)->toBeTrue();
});
