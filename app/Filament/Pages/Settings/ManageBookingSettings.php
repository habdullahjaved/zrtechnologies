<?php

namespace App\Filament\Pages\Settings;

use App\Settings\BookingSettings;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ManageBookingSettings extends BaseSettingsPage
{
    use HasPageShield;

    protected static string $settings = BookingSettings::class;

    protected static ?string $slug = 'settings/booking';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendar;

    protected static ?string $navigationLabel = 'Booking rules';

    protected static ?string $title = 'Booking rules';

    protected static ?int $navigationSort = 25;

    public function getSubheading(): ?string
    {
        return 'Which collection slots customers can book. The weekly timetable and closed days are under Coverage.';
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Collection slots')
                ->columns(3)
                ->columnSpanFull()
                ->schema([
                    TextInput::make('lead_time_hours')
                        ->label('Minimum notice')
                        ->suffix('hours')
                        ->helperText('A window can only be booked if it starts at least this long from now.')
                        ->integer()
                        ->minValue(0)
                        ->maxValue(72)
                        ->required(),
                    TimePicker::make('same_day_cutoff')
                        ->label('Same-day bookings close at')
                        ->seconds(false)
                        ->format('H:i')
                        ->helperText('After this time, the earliest slot is the next working day.')
                        ->required(),
                    TextInput::make('max_days_ahead')
                        ->label('Book up to')
                        ->suffix('days ahead')
                        ->integer()
                        ->minValue(1)
                        ->maxValue(90)
                        ->required(),
                ]),
            Section::make('Outside our area')
                ->columnSpanFull()
                ->schema([
                    Toggle::make('post_in_enabled')
                        ->label('Offer post-in repairs')
                        ->helperText('When on, customers outside every service area are offered a post-in repair instead of being turned away.'),
                ]),
        ]);
    }
}
