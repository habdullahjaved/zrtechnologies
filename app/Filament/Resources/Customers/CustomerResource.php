<?php

namespace App\Filament\Resources\Customers;

use App\Filament\NavigationGroup;
use App\Filament\RelationManagers\NotesRelationManager;
use App\Filament\Resources\Customers\Pages\CreateCustomer;
use App\Filament\Resources\Customers\Pages\EditCustomer;
use App\Filament\Resources\Customers\Pages\ListCustomers;
use App\Filament\Resources\Customers\RelationManagers\AddressesRelationManager;
use App\Filament\Resources\Customers\RelationManagers\LeadsRelationManager;
use App\Filament\Resources\Customers\RelationManagers\RepairJobsRelationManager;
use App\Filament\Resources\Customers\Schemas\CustomerForm;
use App\Filament\Resources\Customers\Tables\CustomersTable;
use App\Filament\Resources\Leads\LeadResource;
use App\Models\Customer;
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

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $modelLabel = 'customer';

    protected static ?string $recordTitleAttribute = 'first_name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Crm;

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return CustomerForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CustomersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RepairJobsRelationManager::class,
            LeadsRelationManager::class,
            AddressesRelationManager::class,
            NotesRelationManager::class,
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['first_name', 'last_name', 'email', 'phone', 'company_name'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        /** @var Customer $record */
        return $record->full_name;
    }

    /**
     * @return array<string, string>
     */
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        /** @var Customer $record */
        return array_filter([
            'Phone' => $record->phone ? Phone::display($record->phone) : null,
            'Email' => $record->email,
        ]);
    }

    /**
     * Phone numbers are stored as +447…; let "07…" searches find them too.
     *
     * @return Collection<int, GlobalSearchResult>
     */
    public static function getGlobalSearchResults(string $search): Collection
    {
        return parent::getGlobalSearchResults(LeadResource::normalisePhoneSearch($search));
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomers::route('/'),
            'create' => CreateCustomer::route('/create'),
            'edit' => EditCustomer::route('/{record}/edit'),
        ];
    }
}
