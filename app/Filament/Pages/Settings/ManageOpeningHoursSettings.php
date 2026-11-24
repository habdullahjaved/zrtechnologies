<?php

namespace App\Filament\Pages\Settings;

use App\Settings\OpeningHoursSettings;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Closure;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ManageOpeningHoursSettings extends BaseSettingsPage
{
    use HasPageShield;

    protected static string $settings = OpeningHoursSettings::class;

    protected static ?string $slug = 'settings/opening-hours';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static ?string $navigationLabel = 'Opening hours';

    protected static ?string $title = 'Opening hours';

    protected static ?int $navigationSort = 20;

    public function getSubheading(): ?string
    {
        return 'One set of hours used in the footer, contact page and search-engine data. Keep them identical to your Google Business Profile.';
    }

    public function form(Schema $schema): Schema
    {
        $days = [];

        foreach (OpeningHoursSettings::DAYS as $day => $label) {
            $days[] = Grid::make(3)
                ->statePath("days.{$day}")
                ->columnSpanFull()
                ->schema([
                    Toggle::make('is_open')
                        ->label($label)
                        ->onIcon(Heroicon::Check)
                        ->inline(false)
                        ->live(),
                    TimePicker::make('opens')
                        ->label('Opens')
                        ->seconds(false)
                        ->format('H:i')
                        ->visible(fn (Get $get): bool => (bool) $get('is_open'))
                        ->required(fn (Get $get): bool => (bool) $get('is_open')),
                    TimePicker::make('closes')
                        ->label('Closes')
                        ->seconds(false)
                        ->format('H:i')
                        ->visible(fn (Get $get): bool => (bool) $get('is_open'))
                        ->required(fn (Get $get): bool => (bool) $get('is_open'))
                        ->rule(fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                            if (filled($value) && filled($get('opens')) && $value <= $get('opens')) {
                                $fail('Closing time must be after opening time.');
                            }
                        }),
                ]);
        }

        return $schema->components([
            Section::make('Weekly hours')
                ->columnSpanFull()
                ->schema($days),
            Section::make('Note')
                ->columnSpanFull()
                ->schema([
                    TextInput::make('note')
                        ->label('Note shown with your hours')
                        ->placeholder('e.g. Collections and returns by appointment within these hours.')
                        ->maxLength(200),
                ]),
        ]);
    }

    /**
     * Closed days do not keep stale times.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        foreach (array_keys(OpeningHoursSettings::DAYS) as $day) {
            $isOpen = (bool) ($data['days'][$day]['is_open'] ?? false);

            $data['days'][$day] = [
                'is_open' => $isOpen,
                'opens' => $isOpen ? substr((string) $data['days'][$day]['opens'], 0, 5) : null,
                'closes' => $isOpen ? substr((string) $data['days'][$day]['closes'], 0, 5) : null,
            ];
        }

        return $data;
    }
}
