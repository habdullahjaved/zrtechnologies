<?php

namespace App\Filament\Resources\Users\Tables;

use App\Enums\StaffRole;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use App\Support\Phone;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->description(fn (User $record): ?string => $record->job_title)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('phone')
                    ->label('Mobile')
                    ->formatStateUsing(fn (?string $state): string => filled($state) ? Phone::display($state) : '')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('roles.name')
                    ->label('Role')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => StaffRole::tryFrom($state)?->getLabel() ?? $state),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                IconColumn::make('two_factor')
                    ->label('2FA')
                    ->state(fn (User $record): bool => filled($record->app_authentication_secret) || $record->has_email_authentication)
                    ->boolean()
                    ->tooltip(fn (bool $state): string => $state ? 'Two-factor sign-in is set up' : 'Two-factor sign-in not set up'),
                TextColumn::make('last_login_at')
                    ->label('Last sign-in')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Never')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Added')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('roles')
                    ->label('Role')
                    ->relationship('roles', 'name')
                    ->getOptionLabelFromRecordUsing(fn ($record): string => StaffRole::tryFrom($record->name)?->getLabel() ?? $record->name),
                TernaryFilter::make('is_active')
                    ->label('Active'),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                ActionGroup::make([
                    Action::make('history')
                        ->label('History')
                        ->icon(Heroicon::OutlinedClock)
                        ->url(fn (User $record): string => UserResource::getUrl('activities', ['record' => $record])),
                    RestoreAction::make(),
                ]),
            ]);
    }
}
