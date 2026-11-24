<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

/**
 * @property User $record
 */
class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('history')
                ->label('History')
                ->icon(Heroicon::OutlinedClock)
                ->color('gray')
                ->url(fn (): string => UserResource::getUrl('activities', ['record' => $this->record])),
            Action::make('resetTwoFactor')
                ->label('Reset two-factor')
                ->icon(Heroicon::OutlinedShieldExclamation)
                ->color('warning')
                ->requiresConfirmation()
                ->modalDescription('Use this if the staff member has lost access to their authenticator app. They will be asked to set up two-factor sign-in again next time they sign in.')
                ->visible(fn (): bool => $this->canResetTwoFactor())
                ->action(function (): void {
                    $this->record->forceFill([
                        'app_authentication_secret' => null,
                        'app_authentication_recovery_codes' => null,
                        'has_email_authentication' => false,
                    ])->save();

                    activity('users')
                        ->performedOn($this->record)
                        ->causedBy(auth()->user())
                        ->log('Two-factor sign-in reset');

                    Notification::make()->title('Two-factor sign-in reset')->success()->send();
                }),
            DeleteAction::make()
                ->hidden(fn (): bool => $this->isOwnAccount()),
            ForceDeleteAction::make()
                ->hidden(fn (): bool => $this->isOwnAccount()),
            RestoreAction::make(),
        ];
    }

    private function isOwnAccount(): bool
    {
        return $this->record->is(auth()->user());
    }

    private function canResetTwoFactor(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && $user->isSuperAdmin()
            && ! $this->record->is($user)
            && (filled($this->record->app_authentication_secret) || $this->record->has_email_authentication);
    }
}
