<?php

use App\Enums\StaffRole;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Spatie\Permission\Models\Role;

it('creates every staff role and the owner account', function () {
    $this->seed(DatabaseSeeder::class);

    expect(Role::pluck('name')->sort()->values()->all())
        ->toBe(collect(StaffRole::cases())->pluck('value')->sort()->values()->all());

    $owner = User::firstWhere('email', 'mrumar@zrtechnologiesltd.com');

    expect($owner)->not->toBeNull()
        ->and($owner->isSuperAdmin())->toBeTrue()
        ->and($owner->name)->toBe('Muhammad Umar Shabbir');
});

it('seeds demo staff outside production only', function () {
    $this->seed(DatabaseSeeder::class);
    expect(User::where('email', 'like', '%@zrtechnologies.test')->count())->toBe(4);
});

it('does not seed demo staff in production', function () {
    app()->detectEnvironment(fn () => 'production');

    $this->artisan('db:seed', ['--force' => true])->assertSuccessful();

    expect(User::where('email', 'like', '%@zrtechnologies.test')->count())->toBe(0)
        ->and(User::count())->toBe(1);
});

it('can be run repeatedly without duplicating data or changing the owner password', function () {
    $this->seed(DatabaseSeeder::class);
    $passwordHash = User::firstWhere('email', 'mrumar@zrtechnologiesltd.com')->password;

    $this->seed(DatabaseSeeder::class);

    expect(Role::count())->toBe(count(StaffRole::cases()))
        ->and(User::where('email', 'mrumar@zrtechnologiesltd.com')->count())->toBe(1)
        ->and(User::firstWhere('email', 'mrumar@zrtechnologiesltd.com')->password)->toBe($passwordHash);
});

it('gives every permission to the super admin role', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $expected = collect(RolesAndPermissionsSeeder::PERMISSIONS)->map(fn (array $actions): int => count($actions))->sum();

    expect(Role::findByName(StaffRole::SuperAdmin->value)->permissions()->count())->toBe($expected);
});
