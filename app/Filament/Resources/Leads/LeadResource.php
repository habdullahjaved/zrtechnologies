<?php

namespace App\Filament\Resources\Leads;

use App\Enums\LeadStatus;
use App\Filament\NavigationGroup;
use App\Filament\RelationManagers\NotesRelationManager;
use App\Filament\Resources\Leads\Pages\CreateLead;
use App\Filament\Resources\Leads\Pages\EditLead;
use App\Filament\Resources\Leads\Pages\ListLeads;
use App\Filament\Resources\Leads\Pages\ViewLead;
use App\Filament\Resources\Leads\Schemas\LeadForm;
use App\Filament\Resources\Leads\Schemas\LeadInfolist;
use App\Filament\Resources\Leads\Tables\LeadsTable;
use App\Models\Lead;
use App\Support\Phone;
use BackedEnum;
use Filament\GlobalSearch\GlobalSearchResult;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use UnitEnum;

class LeadResource extends Resource
{
    protected static ?string $model = Lead::class;

    protected static ?string $modelLabel = 'lead';

    protected static ?string $recordTitleAttribute = 'reference';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxStack;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Crm;

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return LeadForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return LeadInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LeadsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            NotesRelationManager::class,
        ];
    }

    /**
     * New leads still waiting for someone to pick them up.
     */
    public static function getNavigationBadge(): ?string
    {
        $count = Lead::query()->where('status', LeadStatus::New)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): string
    {
        return 'New leads';
    }

    /**
     * @return array<int, string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['reference', 'name', 'email', 'phone', 'postcode'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        /** @var Lead $record */
        return $record->reference.' · '.$record->displayName();
    }

    /**
     * @return array<string, string>
     */
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        /** @var Lead $record */
        return array_filter([
            'Type' => $record->type->getLabel(),
            'Status' => $record->status->getLabel(),
            'Phone' => $record->phone ? Phone::display($record->phone) : null,
        ]);
    }

    /**
     * Phone numbers are stored as +447…; let "07…" searches find them too.
     *
     * @return Collection<int, GlobalSearchResult>
     */
    public static function getGlobalSearchResults(string $search): Collection
    {
        return parent::getGlobalSearchResults(self::normalisePhoneSearch($search));
    }

    public static function normalisePhoneSearch(string $search): string
    {
        $digits = preg_replace('/[\s()-]/', '', $search) ?? $search;

        return preg_match('/^(\+44|0)7\d{9}$/', $digits) === 1 ? Phone::toE164($digits) : $search;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLeads::route('/'),
            'create' => CreateLead::route('/create'),
            'view' => ViewLead::route('/{record}'),
            'edit' => EditLead::route('/{record}/edit'),
        ];
    }
}
