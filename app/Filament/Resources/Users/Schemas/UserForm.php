<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\StaffRole;
use App\Models\User;
use App\Support\Phone;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rules\Password;
use Propaganistas\LaravelPhone\Rules\Phone as PhoneRule;
use Spatie\Permission\Models\Role;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Profile')
                    ->columns(2)
                    ->columnSpan(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(100),
                        TextInput::make('job_title')
                            ->label('Job title')
                            ->maxLength(100),
                        TextInput::make('email')
                            ->label('Email address')
                            ->email()
                            ->required()
                            ->maxLength(150)
                            ->unique(ignoreRecord: true),
                        TextInput::make('phone')
                            ->label('Mobile number')
                            ->tel()
                            ->rule((new PhoneRule)->country(Phone::COUNTRY))
                            ->formatStateUsing(fn (?string $state): ?string => filled($state) ? Phone::display($state) : null)
                            ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? Phone::toE164($state) : null),
                    ]),

                Section::make('Access')
                    ->columnSpan(1)
                    ->schema([
                        Select::make('roles')
                            ->label('Role')
                            ->relationship(
                                name: 'roles',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn (Builder $query): Builder => self::assignableRoles($query),
                            )
                            ->getOptionLabelFromRecordUsing(fn (Role $record): string => StaffRole::tryFrom($record->name)?->getLabel() ?? str($record->name)->headline()->toString())
                            ->multiple()
                            ->preload()
                            ->required()
                            ->helperText('Most staff need one role.'),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->helperText('Inactive staff cannot sign in.')
                            ->default(true)
                            ->disabled(fn (?Model $record): bool => $record?->is(auth()->user()) ?? false),
                    ]),

                Section::make('Password')
                    ->columns(2)
                    ->columnSpan(2)
                    ->description(fn (string $operation): string => $operation === 'create'
                        ? 'Set a temporary password and share it securely. Staff can change it from their profile.'
                        : 'Leave empty to keep the current password.')
                    ->schema([
                        TextInput::make('password')
                            ->password()
                            ->revealable()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->rule(Password::defaults())
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->confirmed(),
                        TextInput::make('password_confirmation')
                            ->label('Confirm password')
                            ->password()
                            ->revealable()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(false),
                    ]),

                Section::make('Security')
                    ->columnSpan(1)
                    ->visibleOn('edit')
                    ->schema([
                        Text::make(fn (?User $record): string => self::securitySummary($record)),
                    ]),
            ]);
    }

    /**
     * Only a super admin can grant the super admin role.
     *
     * @param  Builder<Role>  $query
     * @return Builder<Role>
     */
    private static function assignableRoles(Builder $query): Builder
    {
        $user = auth()->user();

        if ($user instanceof User && $user->isSuperAdmin()) {
            return $query->orderBy('id');
        }

        return $query->where('name', '!=', StaffRole::SuperAdmin->value)->orderBy('id');
    }

    private static function securitySummary(?User $record): string
    {
        if (! $record) {
            return '';
        }

        $methods = array_filter([
            filled($record->app_authentication_secret) ? 'authenticator app' : null,
            $record->has_email_authentication ? 'email code' : null,
        ]);

        $mfa = $methods === []
            ? 'Two-factor sign-in: not set up yet.'
            : 'Two-factor sign-in: '.implode(' and ', $methods).'.';

        $lastLogin = $record->last_login_at
            ? 'Last signed in '.$record->last_login_at->format('d/m/Y H:i').'.'
            : 'Has not signed in yet.';

        return $mfa.' '.$lastLogin;
    }
}
