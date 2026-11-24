<?php

namespace App\Filament\Resources\Reviews;

use App\Enums\ReviewSource;
use App\Filament\NavigationGroup;
use App\Filament\Resources\Reviews\Pages\ManageReviews;
use App\Models\Review;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Genuine customer reviews shown on the website. Copy them exactly as the
 * customer wrote them (UK DMCC Act 2024: no fake, edited or incentivised reviews).
 */
class ReviewResource extends Resource
{
    protected static ?string $model = Review::class;

    protected static ?string $navigationLabel = 'Reviews';

    protected static ?string $recordTitleAttribute = 'reviewer_name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedStar;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Crm;

    protected static ?int $navigationSort = 40;

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            Callout::make('Only genuine reviews')
                ->description('Add reviews exactly as real customers wrote them, for example copied from Google. Never write, edit or pay for reviews: it is against the law (DMCC Act 2024).')
                ->warning()
                ->columnSpanFull(),
            Select::make('source')
                ->options(ReviewSource::class)
                ->default(ReviewSource::Google)
                ->required()
                ->native(false),
            DatePicker::make('reviewed_at')
                ->label('Date of review')
                ->native(false)
                ->displayFormat('d/m/Y')
                ->maxDate(today())
                ->default(today())
                ->required(),
            TextInput::make('reviewer_name')
                ->label('Customer name')
                ->helperText('Shown as first name and initial, e.g. "Sarah K."')
                ->required()
                ->maxLength(120),
            Select::make('rating')
                ->options([5 => '★★★★★ 5', 4 => '★★★★ 4', 3 => '★★★ 3', 2 => '★★ 2', 1 => '★ 1'])
                ->default(5)
                ->required(),
            Textarea::make('body')
                ->label('Review')
                ->required()
                ->rows(4)
                ->maxLength(5000)
                ->columnSpanFull(),
            TextInput::make('source_url')
                ->label('Link to the original review')
                ->url()
                ->maxLength(500),
            Select::make('service_area_id')
                ->label('Customer’s town')
                ->helperText('Local reviews are shown on that town’s page.')
                ->relationship('serviceArea', 'name')
                ->preload(),
            Select::make('repair_job_id')
                ->label('Repair job')
                ->relationship('repairJob', 'reference')
                ->searchable(),
            Toggle::make('is_verified')
                ->label('Verified customer')
                ->helperText('You have matched this review to a real repair.'),
            Toggle::make('is_published')
                ->label('Show on the website')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('reviewed_at', 'desc')
            ->columns([
                TextColumn::make('reviewer_name')
                    ->label('Customer')
                    ->description(fn (Review $record): string => str($record->body)->limit(90)->toString())
                    ->wrap()
                    ->searchable(['reviewer_name', 'body']),
                TextColumn::make('rating')
                    ->formatStateUsing(fn (int $state): string => str_repeat('★', $state))
                    ->color('warning'),
                TextColumn::make('source')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('reviewed_at')
                    ->label('Date')
                    ->date('d/m/Y')
                    ->sortable(),
                IconColumn::make('is_published')
                    ->label('Shown')
                    ->boolean(),
                TextColumn::make('is_sample')
                    ->label('')
                    ->formatStateUsing(fn (bool $state): ?string => $state ? 'Sample (dev only)' : null)
                    ->badge()
                    ->color('danger'),
            ])
            ->filters([
                SelectFilter::make('source')->options(ReviewSource::class),
                SelectFilter::make('rating')->options([5 => '5 stars', 4 => '4 stars', 3 => '3 stars', 2 => '2 stars', 1 => '1 star']),
            ])
            ->modifyQueryUsing(fn (Builder $query) => $query->when(app()->isProduction(), fn (Builder $query) => $query->where('is_sample', false)))
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->iconButton(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageReviews::route('/'),
        ];
    }
}
