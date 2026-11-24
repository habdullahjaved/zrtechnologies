<?php

namespace App\Filament\Resources\Leads;

use App\Actions\Leads\ConvertLeadToJob;
use App\Enums\LeadStatus;
use App\Filament\Resources\CrmFields;
use App\Filament\Resources\RepairJobs\RepairJobResource;
use App\Models\AvailabilityWindow;
use App\Models\Lead;
use App\Models\RepairJob;
use App\Models\User;
use App\Settings\BusinessSettings;
use App\Support\Messaging\MessageRenderer;
use App\Support\Messaging\MessageTemplateCatalogue;
use App\Support\Phone;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

/**
 * One-click lead actions, shared by the leads table and the lead page.
 */
final class LeadActions
{
    public static function call(): Action
    {
        return Action::make('call')
            ->label('Call')
            ->icon(Heroicon::OutlinedPhone)
            ->color('gray')
            ->visible(fn (Lead $record): bool => filled($record->phone))
            ->url(fn (Lead $record): string => Phone::telHref((string) $record->phone));
    }

    public static function whatsApp(): Action
    {
        return Action::make('whatsapp')
            ->label('WhatsApp')
            ->icon(Heroicon::OutlinedChatBubbleLeftRight)
            ->color('success')
            ->visible(fn (Lead $record): bool => filled($record->phone))
            ->url(fn (Lead $record): string => Phone::whatsAppUrl((string) $record->phone, self::whatsAppMessage($record)), shouldOpenInNewTab: true);
    }

    public static function email(): Action
    {
        return Action::make('email')
            ->label('Email')
            ->icon(Heroicon::OutlinedEnvelope)
            ->color('gray')
            ->visible(fn (Lead $record): bool => filled($record->email))
            ->url(fn (Lead $record): string => 'mailto:'.$record->email.'?subject='.rawurlencode('Your enquiry '.$record->reference.' – '.app(BusinessSettings::class)->trading_name));
    }

    public static function changeStatus(): Action
    {
        return Action::make('changeStatus')
            ->label('Update status')
            ->icon(Heroicon::OutlinedArrowPath)
            ->color('primary')
            ->authorize(fn (Lead $record): bool => auth()->user()?->can('update', $record) ?? false)
            ->fillForm(fn (Lead $record): array => [
                'status' => $record->status->value,
                'lost_reason' => $record->lost_reason,
                'follow_up_at' => $record->follow_up_at,
            ])
            ->schema([
                Select::make('status')
                    ->options(LeadStatus::class)
                    ->live()
                    ->required(),
                TextInput::make('lost_reason')
                    ->label('Why was it lost?')
                    ->maxLength(255)
                    ->visible(fn (Get $get): bool => in_array($get('status'), [LeadStatus::Lost, LeadStatus::Lost->value], true))
                    ->required(fn (Get $get): bool => in_array($get('status'), [LeadStatus::Lost, LeadStatus::Lost->value], true)),
                DateTimePicker::make('follow_up_at')
                    ->label('Follow up')
                    ->seconds(false)
                    ->native(false)
                    ->displayFormat('d/m/Y H:i'),
            ])
            ->action(function (Lead $record, array $data): void {
                $status = $data['status'] instanceof LeadStatus ? $data['status'] : LeadStatus::from($data['status']);

                $record->fill([
                    'status' => $status,
                    'lost_reason' => $status === LeadStatus::Lost ? $data['lost_reason'] : null,
                    'follow_up_at' => $data['follow_up_at'] ?? null,
                ]);

                if ($status !== LeadStatus::New && $record->contacted_at === null) {
                    $record->contacted_at = now();
                }

                $record->assigned_to ??= auth()->id();
                $record->save();

                Notification::make()->success()->title('Lead updated')->send();
            });
    }

    public static function assignToMe(): Action
    {
        return Action::make('assignToMe')
            ->label('Assign to me')
            ->icon(Heroicon::OutlinedUserCircle)
            ->color('gray')
            ->visible(fn (Lead $record): bool => $record->assigned_to !== auth()->id())
            ->authorize(fn (Lead $record): bool => auth()->user()?->can('update', $record) ?? false)
            ->action(function (Lead $record): void {
                $record->update(['assigned_to' => auth()->id()]);

                Notification::make()->success()->title('Assigned to you')->send();
            });
    }

    public static function convertToJob(): Action
    {
        return Action::make('convertToJob')
            ->label('Convert to repair job')
            ->icon(Heroicon::OutlinedWrenchScrewdriver)
            ->color('success')
            ->visible(fn (Lead $record): bool => $record->repairJob === null && ! in_array($record->status, [LeadStatus::Lost, LeadStatus::Completed], true))
            ->authorize(fn (): bool => auth()->user()?->can('create', RepairJob::class) ?? false)
            ->modalHeading(fn (Lead $record): string => 'Create a repair job for '.$record->displayName())
            ->modalDescription('The customer record is created or matched by phone and email.')
            ->modalSubmitActionLabel('Create job')
            ->fillForm(fn (Lead $record): array => [
                'device_model_id' => $record->device_model_id,
                'reported_fault' => $record->message ?? $record->repairType?->name,
                'quoted_price_pence' => $record->estimated_value_pence,
                'postcode' => $record->postcode,
            ])
            ->schema([
                Grid::make(2)->schema([
                    Select::make('device_model_id')
                        ->label('iPhone model')
                        ->relationship('deviceModel', 'name', fn (Builder $query) => $query->orderBy('sort'))
                        ->searchable()
                        ->preload(),
                    CrmFields::pounds('quoted_price_pence', 'Quoted price'),
                    Textarea::make('reported_fault')
                        ->label('Fault')
                        ->rows(2)
                        ->columnSpanFull(),
                    DatePicker::make('collection_date')
                        ->label('Collection date')
                        ->native(false)
                        ->displayFormat('d/m/Y')
                        ->live(),
                    Select::make('availability_window_id')
                        ->label('Collection window')
                        ->options(fn (Get $get): array => self::windowOptions($get('collection_date'))),
                    TextInput::make('line1')->label('Address line 1')->maxLength(120),
                    TextInput::make('line2')->label('Address line 2')->maxLength(120),
                    TextInput::make('town')->maxLength(80),
                    TextInput::make('postcode')->maxLength(10),
                    Select::make('technician_id')
                        ->label('Technician')
                        ->options(fn (): array => User::query()->where('is_active', true)->whereHas('roles')->orderBy('name')->pluck('name', 'id')->all()),
                ]),
            ])
            ->action(function (Lead $record, array $data, Action $action): void {
                $job = app(ConvertLeadToJob::class)->handle($record, $data, auth()->user() instanceof User ? auth()->user() : null);

                Notification::make()->success()->title("Repair job {$job->reference} created")->send();

                $action->redirect(RepairJobResource::getUrl('view', ['record' => $job]));
            });
    }

    public static function openJob(): Action
    {
        return Action::make('openJob')
            ->label(fn (Lead $record): string => 'Job '.$record->repairJob?->reference)
            ->icon(Heroicon::OutlinedWrenchScrewdriver)
            ->color('gray')
            ->visible(fn (Lead $record): bool => $record->repairJob !== null && RepairJobResource::canView($record->repairJob))
            ->url(fn (Lead $record): ?string => $record->repairJob ? RepairJobResource::getUrl('view', ['record' => $record->repairJob]) : null);
    }

    /**
     * @return array<int, string>
     */
    public static function windowOptions(mixed $date): array
    {
        $query = AvailabilityWindow::query()->where('is_active', true)->orderBy('weekday')->orderBy('starts_at');

        if (filled($date)) {
            $query->where('weekday', now()->parse((string) $date)->isoWeekday());
        }

        return $query->get()->mapWithKeys(fn (AvailabilityWindow $window): array => [
            $window->getKey() => (filled($date) ? '' : $window->weekdayName().' ').$window->timeRange(),
        ])->all();
    }

    private static function whatsAppMessage(Lead $lead): string
    {
        $renderer = app(MessageRenderer::class);
        $template = $renderer->template(MessageTemplateCatalogue::WHATSAPP_LEAD_REPLY);

        if ($template === null) {
            return '';
        }

        return $renderer->render($template->body, [
            'customer_first_name' => $lead->firstName(),
            'staff_first_name' => str((string) auth()->user()?->name)->before(' ')->toString(),
            'device' => $lead->deviceModel->name ?? 'iPhone',
            'reference' => $lead->reference,
        ], escape: false);
    }
}
