<?php

use App\Enums\StaffRole;
use App\Filament\Pages\Settings\ManageBusinessSettings;
use App\Filament\Resources\Activities\ActivityResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Auth\Pages\Login;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    config(['zr.admin.require_mfa' => false]);
});

it('redirects guests to the admin sign-in page', function () {
    $this->get('/admin')->assertRedirect('/admin/login');
});

it('lets active staff with a role into the admin', function (StaffRole $role) {
    $user = User::factory()->withRole($role)->create();

    $this->actingAs($user)->get('/admin')->assertOk();
})->with(StaffRole::cases());

it('blocks inactive staff and accounts without a role', function () {
    $this->actingAs(User::factory()->inactive()->superAdmin()->create())->get('/admin')->assertForbidden();
    $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden();
});

it('requires two-factor set-up when MFA is mandatory', function () {
    config(['zr.admin.require_mfa' => true]);

    $this->actingAs(User::factory()->superAdmin()->create())
        ->get('/admin')
        ->assertRedirectContains('multi-factor-authentication');
});

it('gives each role the right access to staff, settings and the audit log', function (StaffRole $role, bool $staff, bool $businessSettings, bool $auditLog) {
    $user = User::factory()->withRole($role)->create();
    $this->actingAs($user);

    $this->get(UserResource::getUrl('index'))->assertStatus($staff ? 200 : 403);
    $this->get(ManageBusinessSettings::getUrl())->assertStatus($businessSettings ? 200 : 403);
    $this->get(ActivityResource::getUrl('index'))->assertStatus($auditLog ? 200 : 403);
})->with([
    'super admin' => [StaffRole::SuperAdmin, true, true, true],
    'manager' => [StaffRole::Manager, true, true, true],
    'technician' => [StaffRole::Technician, false, false, false],
    'content editor' => [StaffRole::ContentEditor, false, false, false],
    'sales/support' => [StaffRole::SalesSupport, false, false, false],
]);

it('lets content editors manage website and social settings only', function () {
    $this->actingAs(User::factory()->withRole(StaffRole::ContentEditor)->create());

    $this->get('/admin/settings/website')->assertOk();
    $this->get('/admin/settings/social')->assertOk();
    $this->get('/admin/settings/opening-hours')->assertForbidden();
});

it('shows each role only the settings pages it may open in the sidebar', function () {
    $this->actingAs(User::factory()->withRole(StaffRole::ContentEditor)->create())
        ->get('/admin')
        ->assertOk()
        ->assertSee('Website &amp; SEO', false)
        ->assertSee('Social &amp; listings', false)
        ->assertDontSee('Business details')
        ->assertDontSee('Opening hours');
});

it('stops managers from editing or deleting a super admin', function () {
    $manager = User::factory()->withRole(StaffRole::Manager)->create();
    $owner = User::factory()->superAdmin()->create();
    $technician = User::factory()->withRole(StaffRole::Technician)->create();

    expect($manager->can('update', $owner))->toBeFalse()
        ->and($manager->can('delete', $owner))->toBeFalse()
        ->and($manager->can('update', $technician))->toBeTrue()
        ->and($manager->can('delete', $technician))->toBeTrue()
        ->and($manager->can('delete', $manager))->toBeFalse();
});

it('records when staff sign in', function () {
    $user = User::factory()->superAdmin()->create(['password' => 'correct-horse-battery']);

    Livewire\Livewire::test(Login::class)
        ->fillForm(['email' => $user->email, 'password' => 'correct-horse-battery'])
        ->call('authenticate')
        ->assertHasNoFormErrors();

    expect($user->fresh()->last_login_at)->not->toBeNull();
});
