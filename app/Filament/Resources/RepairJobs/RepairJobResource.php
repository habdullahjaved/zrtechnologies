<?php

namespace App\Filament\Resources\RepairJobs;

use App\Enums\JobStatus;
use App\Filament\NavigationGroup;
use App\Filament\RelationManagers\NotesRelationManager;
use App\Filament\Resources\Leads\LeadResource;
use App\Filament\Resources\RepairJobs\Pages\CreateRepairJob;
use App\Filament\Resources\RepairJobs\Pages\EditRepairJob;
use App\Filament\Resources\RepairJobs\Pages\ListRepairJobs;
use App\Filament\Resources\RepairJobs\Pages\ViewRepairJob;
use App\Filament\Resources\RepairJobs\RelationManagers\StatusHistoryRelationManager;
use App\Filament\Resources\RepairJobs\Schemas\RepairJobForm;
use App\Filament\Resources\RepairJobs\Schemas\RepairJobInfolist;
use App\Filament\Resources\RepairJobs\Tables\RepairJobsTable;
use App\Models\RepairJob;
use App\Models\User;
use App\Policies\RepairJobPolicy;
use BackedEnum;
use Filament\GlobalSearch\GlobalSearchResult;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use UnitEnum;

class RepairJobResource extends Resource
{
    protected static ?string $model = RepairJob::class;

    protected static ?string $modelLabel = 'repair job';

    protected static ?string $navigationLabel = 'Repair jobs';

    protected static ?string $recordTitleAttribute = 'reference';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Workshop;

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return RepairJobForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return RepairJobInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RepairJobsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            StatusHistoryRelationManager::class,
            NotesRelationManager::class,
        ];
    }

    /**
     * Technicians only see the jobs assigned to them.
     *
     * @return Builder<RepairJob>
     */
    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        return RepairJobPolicy::scopeFor(RepairJob::query(), $user instanceof User ? $user : null);
    }

    /**
     * Prices, totals and payment are hidden from staff without "ViewFinancials:RepairJob".
     */
    public static function canViewFinancials(): bool
    {
        return auth()->user()?->can('viewFinancials', RepairJob::class) ?? false;
    }

    /**
     * Office staff (who see every job) manage customers, collection and prices; technicians do not.
     */
    public static function isOfficeUser(): bool
    {
        return auth()->user()?->can('viewAll', RepairJob::class) ?? false;
    }

    /**
     * Jobs waiting for collection today or overdue, shown as a sidebar badge.
     */
    public static function getNavigationBadge(): ?string
    {
        $count = static::getEloquentQuery()
            ->whereIn('status', JobStatus::holdingSlot())
            ->whereDate('collection_date', '<=', today())
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeTooltip(): string
    {
        return 'Collections due today or overdue';
    }

    /**
     * @return array<int, string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['reference', 'customer.first_name', 'customer.last_name', 'customer.phone'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        /** @var RepairJob $record */
        return $record->reference.' · '.$record->customer->full_name;
    }

    /**
     * @return Builder<RepairJob>
     */
    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return static::getEloquentQuery()->with(['customer', 'deviceModel']);
    }

    /**
     * @return array<string, string>
     */
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        /** @var RepairJob $record */
        return array_filter([
            'Device' => $record->deviceModel?->name,
            'Status' => $record->status->getLabel(),
        ]);
    }

    /**
     * IMEIs are encrypted, so an IMEI search matches on its keyed hash; phone
     * searches are normalised to +44 format.
     *
     * @return Collection<int, GlobalSearchResult>
     */
    public static function getGlobalSearchResults(string $search): Collection
    {
        $digits = preg_replace('/\D/', '', $search) ?? '';

        if (strlen($digits) >= 14 && strlen($digits) <= 17 && strlen($digits) === strlen(preg_replace('/[\s-]/', '', $search) ?? '')) {
            return static::getGlobalSearchEloquentQuery()
                ->where('imei_hash', RepairJob::hashImei($digits))
                ->limit(static::getGlobalSearchResultsLimit())
                ->get()
                ->map(fn (RepairJob $record): GlobalSearchResult => new GlobalSearchResult(
                    title: static::getGlobalSearchResultTitle($record),
                    url: static::getUrl('view', ['record' => $record]),
                    details: ['IMEI' => 'Match', ...static::getGlobalSearchResultDetails($record)],
                ));
        }

        return parent::getGlobalSearchResults(LeadResource::normalisePhoneSearch($search));
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRepairJobs::route('/'),
            'create' => CreateRepairJob::route('/create'),
            'view' => ViewRepairJob::route('/{record}'),
            'edit' => EditRepairJob::route('/{record}/edit'),
        ];
    }
}
