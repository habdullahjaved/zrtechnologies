<?php

use App\Filament\Pages\Settings\ManageBusinessSettings;
use App\Filament\Pages\Settings\ManageOpeningHoursSettings;
use App\Filament\Pages\Settings\ManageSiteSettings;
use App\Models\User;
use App\Settings\BusinessSettings;
use App\Settings\OpeningHoursSettings;
use App\Settings\SiteSettings;
use Database\Seeders\RolesAndPermissionsSeeder;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    config(['zr.admin.require_mfa' => false]);
    $this->actingAs(User::factory()->superAdmin()->create());
});

it('shows phone numbers in UK format and saves them in E.164', function () {
    livewire(ManageBusinessSettings::class)
        ->assertSchemaStateSet(['phone' => '07360 127538'])
        ->fillForm(['phone' => '07400 123456', 'whatsapp' => '+44 7400 123457', 'registered_office_postcode' => 'bb2 4ny'])
        ->call('save')
        ->assertHasNoFormErrors();

    $business = freshSettings(BusinessSettings::class);

    expect($business->phone)->toBe('+447400123456')
        ->and($business->whatsapp)->toBe('+447400123457')
        ->and($business->registered_office_postcode)->toBe('BB2 4NY');
});

it('rejects invalid phone numbers and missing required details', function () {
    livewire(ManageBusinessSettings::class)
        ->fillForm(['phone' => '12345', 'company_number' => '', 'email' => 'not-an-email'])
        ->call('save')
        ->assertHasFormErrors(['phone', 'company_number' => 'required', 'email' => 'email']);
});

it('saves opening hours and clears the times of closed days', function () {
    livewire(ManageOpeningHoursSettings::class)
        ->fillForm([
            'days.saturday' => ['is_open' => false, 'opens' => '10:00', 'closes' => '17:00'],
            'days.sunday' => ['is_open' => true, 'opens' => '11:00', 'closes' => '15:00'],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $days = freshSettings(OpeningHoursSettings::class)->days;

    expect($days['saturday'])->toEqual(['is_open' => false, 'opens' => null, 'closes' => null])
        ->and($days['sunday'])->toEqual(['is_open' => true, 'opens' => '11:00', 'closes' => '15:00']);
});

it('rejects a closing time before the opening time', function () {
    livewire(ManageOpeningHoursSettings::class)
        ->fillForm(['days.monday' => ['is_open' => true, 'opens' => '18:00', 'closes' => '09:00']])
        ->call('save')
        ->assertHasFormErrors(['days.monday.closes']);
});

it('saves website and SEO defaults', function () {
    livewire(ManageSiteSettings::class)
        ->fillForm(['announcement' => 'Open on Boxing Day', 'allow_indexing' => true])
        ->call('save')
        ->assertHasNoFormErrors();

    $site = freshSettings(SiteSettings::class);

    expect($site->announcement)->toBe('Open on Boxing Day')
        ->and($site->allow_indexing)->toBeTrue();
});
