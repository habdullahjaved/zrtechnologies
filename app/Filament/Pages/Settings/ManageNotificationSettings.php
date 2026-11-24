<?php

namespace App\Filament\Pages\Settings;

use App\Settings\NotificationSettings;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ManageNotificationSettings extends BaseSettingsPage
{
    use HasPageShield;

    protected static string $settings = NotificationSettings::class;

    protected static ?string $slug = 'settings/notifications';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBellAlert;

    protected static ?string $navigationLabel = 'Team alerts';

    protected static ?string $title = 'Team alerts';

    protected static ?int $navigationSort = 30;

    public function getSubheading(): ?string
    {
        return 'Who hears about new bookings and enquiries. The wording is under Message templates.';
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Email')
                ->columnSpanFull()
                ->schema([
                    TagsInput::make('staff_emails')
                        ->label('Send alerts to')
                        ->placeholder('Add an email address')
                        ->nestedRecursiveRules(['email'])
                        ->helperText('Press Enter after each address.'),
                    Toggle::make('notify_new_bookings')
                        ->label('New online bookings'),
                    Toggle::make('notify_new_enquiries')
                        ->label('New enquiries (quotes, callbacks, contact and business forms, outside-area requests)'),
                    Toggle::make('notify_partial_bookings')
                        ->label('Unfinished bookings')
                        ->helperText('When someone gives their mobile or email in the booking form but does not finish.'),
                    Toggle::make('send_review_requests')
                        ->label('Email customers a Google review request')
                        ->helperText('Sent once, a few hours after a repair is marked completed. Needs the Google review link in Settings → Social.'),
                ]),
            Section::make('Chat apps')
                ->description('Optional. Alerts go to these as well as email.')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    TextInput::make('slack_webhook_url')
                        ->label('Slack incoming webhook URL')
                        ->url()
                        ->maxLength(500)
                        ->placeholder('https://hooks.slack.com/services/…')
                        ->columnSpanFull(),
                    TextInput::make('telegram_bot_token')
                        ->label('Telegram bot token')
                        ->password()
                        ->revealable()
                        ->maxLength(200)
                        ->helperText('From @BotFather. Stored encrypted.'),
                    TextInput::make('telegram_chat_id')
                        ->label('Telegram chat ID')
                        ->maxLength(60),
                ]),
        ]);
    }
}
