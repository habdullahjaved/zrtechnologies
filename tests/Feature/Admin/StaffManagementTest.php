<?php

use App\Enums\StaffRole;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Actions\DeleteAction;
use Spatie\Permission\Models\Role;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    config(['zr.admin.require_mfa' => false]);
    $this->owner = User::factory()->superAdmin()->create();
    $this->actingAs($this->owner);
});

it('lists staff members', function () {
    $staff = User::factory()->count(3)->withRole(StaffRole::Technician)->create();

    livewire(ListUsers::class)->assertCanSeeTableRecords($staff);
});

it('creates a staff member with a role and a normalised phone number', function () {
    livewire(CreateUser::class)
        ->fillForm([
            'name' => 'Daniel Haworth',
            'email' => 'daniel@example.co.uk',
            'phone' => '07400 123458',
            'job_title' => 'Repair Technician',
            'roles' => [Role::findByName(StaffRole::Technician->value)->id],
            'is_active' => true,
            'password' => 'a-strong-password-1',
            'password_confirmation' => 'a-strong-password-1',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $user = User::firstWhere('email', 'daniel@example.co.uk');

    expect($user->phone)->toBe('+447400123458')
        ->and($user->hasRole(StaffRole::Technician->value))->toBeTrue()
        ->and(Hash::check('a-strong-password-1', $user->password))->toBeTrue();
});

it('keeps the current password when the password field is left empty', function () {
    $user = User::factory()->withRole(StaffRole::Technician)->create(['password' => 'original-password']);

    livewire(EditUser::class, ['record' => $user->getRouteKey()])
        ->fillForm(['name' => 'Renamed Technician', 'password' => '', 'password_confirmation' => ''])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($user->fresh()->name)->toBe('Renamed Technician')
        ->and(Hash::check('original-password', $user->fresh()->password))->toBeTrue();
});

it('requires a unique email and a confirmed password', function () {
    livewire(CreateUser::class)
        ->fillForm([
            'name' => 'Duplicate',
            'email' => $this->owner->email,
            'roles' => [Role::findByName(StaffRole::Technician->value)->id],
            'password' => 'a-strong-password-1',
            'password_confirmation' => 'something-else',
        ])
        ->call('create')
        ->assertHasFormErrors(['email' => 'unique', 'password' => 'confirmed']);
});

it('hides the delete action on your own account', function () {
    livewire(EditUser::class, ['record' => $this->owner->getRouteKey()])
        ->assertActionHidden(DeleteAction::class);

    $other = User::factory()->withRole(StaffRole::Technician)->create();

    livewire(EditUser::class, ['record' => $other->getRouteKey()])
        ->assertActionVisible(DeleteAction::class);
});

it('logs changes to staff accounts in the audit log', function () {
    $user = User::factory()->withRole(StaffRole::Technician)->create();
    $user->update(['job_title' => 'Lead Technician']);

    $this->assertDatabaseHas('activity_log', [
        'subject_type' => User::class,
        'subject_id' => $user->id,
        'event' => 'updated',
        'causer_id' => $this->owner->id,
    ]);
});

it('only lets super admins grant the super admin role', function () {
    $manager = User::factory()->withRole(StaffRole::Manager)->create();
    $this->actingAs($manager);

    livewire(CreateUser::class)
        ->assertFormFieldExists('roles', function ($field): bool {
            $options = $field->getOptions();

            return ! in_array('Super Admin', $options, true) && in_array('Technician', $options, true);
        });
});
