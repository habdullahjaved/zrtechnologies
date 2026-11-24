<?php

namespace App\Filament\Pages\Settings;

use App\Settings\BusinessSettings;
use App\Support\Phone;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Propaganistas\LaravelPhone\Rules\Phone as PhoneRule;

class ManageBusinessSettings extends BaseSettingsPage
{
    use HasPageShield;

    protected static string $settings = BusinessSettings::class;

    protected static ?string $slug = 'settings/business';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static ?string $navigationLabel = 'Business details';

    protected static ?string $title = 'Business details';

    protected static ?int $navigationSort = 10;

    public function getSubheading(): ?string
    {
        return 'Your name, address and phone (NAP). Keep these identical to your Google Business Profile: they appear in the header, footer, emails and search-engine data.';
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identity')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    TextInput::make('trading_name')
                        ->label('Trading name')
                        ->required()
                        ->maxLength(100),
                    TextInput::make('legal_name')
                        ->label('Registered company name')
                        ->required()
                        ->maxLength(150),
                    TextInput::make('tagline')
                        ->required()
                        ->maxLength(100),
                    TextInput::make('price_range')
                        ->label('Price range')
                        ->helperText('Used in search-engine data, e.g. ££.')
                        ->required()
                        ->maxLength(5),
                    Textarea::make('short_description')
                        ->label('Short description')
                        ->helperText('One or two sentences describing what you do. Used in search-engine data and the footer.')
                        ->required()
                        ->rows(3)
                        ->maxLength(500)
                        ->columnSpanFull(),
                    TextInput::make('service_area_summary')
                        ->label('Service area summary')
                        ->helperText('Shown in the footer, e.g. "Blackburn, Darwen, Accrington and across East Lancashire".')
                        ->required()
                        ->maxLength(200)
                        ->columnSpanFull(),
                ]),

            Section::make('Contact')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    TextInput::make('phone')
                        ->label('Phone number')
                        ->tel()
                        ->required()
                        ->rule((new PhoneRule)->country(Phone::COUNTRY))
                        ->helperText('Shown on every page and used for click-to-call.'),
                    TextInput::make('whatsapp')
                        ->label('WhatsApp number')
                        ->tel()
                        ->required()
                        ->rule((new PhoneRule)->country(Phone::COUNTRY)->mobile()),
                    TextInput::make('email')
                        ->label('Email address')
                        ->email()
                        ->required()
                        ->maxLength(150),
                    TextInput::make('website_url')
                        ->label('Website address')
                        ->url()
                        ->required()
                        ->maxLength(150),
                ]),

            Section::make('Company details')
                ->description('Required by UK law on your website (Companies Act trading disclosures).')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    TextInput::make('company_number')
                        ->label('Company number')
                        ->required()
                        ->maxLength(8),
                    TextInput::make('registered_in')
                        ->label('Registered in')
                        ->required()
                        ->maxLength(50),
                    TextInput::make('director_name')
                        ->label('Director')
                        ->required()
                        ->maxLength(100),
                    TextInput::make('founded_year')
                        ->label('Year founded')
                        ->numeric()
                        ->minValue(1990)
                        ->maxValue((int) date('Y')),
                    TextInput::make('ico_registration_number')
                        ->label('ICO registration number')
                        ->helperText('Shown in the footer once filled.')
                        ->maxLength(20),
                    TextInput::make('vat_number')
                        ->label('VAT number')
                        ->helperText('Leave empty until VAT registered. Shown in the footer and on invoices once filled.')
                        ->maxLength(20),
                ]),

            Section::make('Registered office')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    TextInput::make('registered_office_line1')
                        ->label('Address line 1')
                        ->required()
                        ->maxLength(150),
                    TextInput::make('registered_office_line2')
                        ->label('Address line 2')
                        ->maxLength(150),
                    TextInput::make('registered_office_town')
                        ->label('Town')
                        ->required()
                        ->maxLength(100),
                    TextInput::make('registered_office_county')
                        ->label('County')
                        ->maxLength(100),
                    TextInput::make('registered_office_postcode')
                        ->label('Postcode')
                        ->required()
                        ->maxLength(10),
                    TextInput::make('registered_office_country')
                        ->label('Country')
                        ->required()
                        ->maxLength(100),
                    Toggle::make('show_street_address')
                        ->label('Show the street address to customers as a shop location')
                        ->helperText('Leave off while you are a collection-and-delivery (service-area) business with no walk-in shop. The registered office still appears in the footer company details, as the law requires.')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['phone'] = Phone::display($data['phone']);
        $data['whatsapp'] = Phone::display($data['whatsapp']);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['phone'] = Phone::toE164($data['phone']);
        $data['whatsapp'] = Phone::toE164($data['whatsapp']);
        $data['registered_office_postcode'] = mb_strtoupper(trim($data['registered_office_postcode']));

        return $data;
    }
}
