<?php

namespace App\Filament\Resources\RepairJobs;

use App\Actions\Jobs\ChangeJobStatus;
use App\Enums\JobStatus;
use App\Models\RepairJob;
use App\Models\User;
use App\Support\Messaging\MessageRenderer;
use App\Support\Messaging\MessageTemplateCatalogue;
use App\Support\Messaging\MessageVariables;
use App\Support\Phone;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;

/**
 * Workshop actions, shared by the jobs table and the job page.
 */
final class RepairJobActions
{
    public static function changeStatus(): Action
    {
        return Action::make('changeStatus')
            ->label('Change status')
            ->icon(Heroicon::OutlinedArrowPathRoundedSquare)
            ->color('primary')
            ->visible(fn (RepairJob $record): bool => $record->status->nextStatuses() !== [])
            ->authorize(fn (RepairJob $record): bool => auth()->user()?->can('update', $record) ?? false)
            ->modalHeading(fn (RepairJob $record): string => 'Move '.$record->reference.' on')
            ->modalDescription(fn (RepairJob $record): string => 'Currently: '.$record->status->getLabel().'.')
            ->modalSubmitActionLabel('Save status')
            ->schema([
                Select::make('status')
                    ->label('New status')
                    ->options(fn (RepairJob $record): array => collect($record->status->nextStatuses())
                        ->mapWithKeys(fn (JobStatus $status): array => [$status->value => $status->getLabel()])
                        ->all())
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn (?string $state, callable $set) => $set('notify', $state !== null && JobStatus::from($state)->notifiesCustomerByDefault())),
                Textarea::make('customer_message')
                    ->label('Message for the customer')
                    ->helperText('Optional. Shown on their tracking page and in the email, e.g. "The new screen arrives tomorrow."')
                    ->rows(3)
                    ->maxLength(1000),
                Textarea::make('note')
                    ->label('Internal note')
                    ->helperText('Only the team sees this.')
                    ->rows(2)
                    ->maxLength(2000),
                Toggle::make('notify')
                    ->label('Email the customer about this change')
                    ->default(false)
                    ->helperText(fn (RepairJob $record, Get $get): string => match (true) {
                        blank($record->customer->email) => 'This customer has no email address.',
                        filled($get('status')) && ! app(ChangeJobStatus::class)->canNotify($record, JobStatus::from($get('status'))) => 'There is no active email template for this status.',
                        default => 'Uses the "Repair update" template for the new status.',
                    }),
            ])
            ->action(function (RepairJob $record, array $data): void {
                $user = auth()->user();

                $change = app(ChangeJobStatus::class)->handle(
                    $record,
                    JobStatus::from($data['status']),
                    $user instanceof User ? $user : null,
                    $data['note'] ?? null,
                    $data['customer_message'] ?? null,
                    (bool) ($data['notify'] ?? false),
                );

                Notification::make()
                    ->success()
                    ->title('Status changed to '.$change->to_status->getLabel())
                    ->body($change->customer_notified ? 'The customer will be emailed.' : null)
                    ->send();
            });
    }

    public static function assignTechnician(): Action
    {
        return Action::make('assignTechnician')
            ->label(fn (RepairJob $record): string => $record->technician_id ? 'Reassign' : 'Assign technician')
            ->icon(Heroicon::OutlinedUserCircle)
            ->color('gray')
            ->visible(fn (): bool => RepairJobResource::isOfficeUser())
            ->authorize(fn (RepairJob $record): bool => auth()->user()?->can('update', $record) ?? false)
            ->fillForm(fn (RepairJob $record): array => ['technician_id' => $record->technician_id])
            ->schema([
                Select::make('technician_id')
                    ->label('Technician')
                    ->options(fn (): array => User::query()->where('is_active', true)->whereHas('roles')->orderBy('name')->pluck('name', 'id')->all())
                    ->placeholder('Unassigned'),
            ])
            ->action(function (RepairJob $record, array $data): void {
                $record->update(['technician_id' => $data['technician_id'] ?? null]);

                Notification::make()->success()->title('Technician updated')->send();
            });
    }

    public static function call(): Action
    {
        return Action::make('call')
            ->label('Call customer')
            ->icon(Heroicon::OutlinedPhone)
            ->color('gray')
            ->visible(fn (RepairJob $record): bool => filled($record->customer->phone))
            ->url(fn (RepairJob $record): string => Phone::telHref((string) $record->customer->phone));
    }

    public static function whatsApp(): Action
    {
        return Action::make('whatsapp')
            ->label('WhatsApp')
            ->icon(Heroicon::OutlinedChatBubbleLeftRight)
            ->color('success')
            ->visible(fn (RepairJob $record): bool => filled($record->customer->phone))
            ->url(fn (RepairJob $record): string => Phone::whatsAppUrl((string) $record->customer->phone, self::whatsAppMessage($record)), shouldOpenInNewTab: true);
    }

    public static function directions(): Action
    {
        return Action::make('directions')
            ->label('Directions')
            ->icon(Heroicon::OutlinedMap)
            ->color('gray')
            ->visible(fn (RepairJob $record): bool => $record->collectionAddress !== null)
            ->url(fn (RepairJob $record): ?string => ($record->status->holdsCollectionSlot() ? $record->collectionAddress : $record->deliveryAddress())?->directionsUrl(), shouldOpenInNewTab: true);
    }

    public static function trackingPage(): Action
    {
        return Action::make('trackingPage')
            ->label('Customer’s tracking page')
            ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
            ->color('gray')
            ->url(fn (RepairJob $record): string => MessageVariables::trackUrl($record), shouldOpenInNewTab: true);
    }

    private static function whatsAppMessage(RepairJob $job): string
    {
        $renderer = app(MessageRenderer::class);
        $template = $renderer->template(MessageTemplateCatalogue::WHATSAPP_JOB_UPDATE);

        if ($template === null) {
            return '';
        }

        return $renderer->render($template->body, [
            'customer_first_name' => $job->customer->first_name,
            'staff_first_name' => str((string) auth()->user()?->name)->before(' ')->toString(),
            'reference' => $job->reference,
            'status' => $job->status->customerLabel(),
            'track_url' => MessageVariables::trackUrl($job),
        ], escape: false);
    }
}
