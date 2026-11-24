<?php

namespace App\Filament\Resources\DeviceModels\RelationManagers;

use App\Filament\Resources\ContentFields;
use App\Models\DeviceModel;
use App\Models\ModelRepairPage;
use App\Settings\SiteSettings;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Unique;

/**
 * Optional deeper pages for one repair on this model, e.g.
 * /iphone-repair/iphone-15-pro/screen-replacement. They stay noindex until
 * their own content passes the unique-content checks.
 */
class RepairPagesRelationManager extends RelationManager
{
    protected static string $relationship = 'repairPages';

    protected static ?string $title = 'Model × repair pages';

    protected static ?string $modelLabel = 'model × repair page';

    public function form(Schema $schema): Schema
    {
        $site = app(SiteSettings::class);

        return $schema->components([
            Callout::make('Only for genuinely different content')
                ->description("Create one of these only when this repair on this model needs its own explanation. It stays hidden from search engines until it has at least {$site->min_unique_words_model_repair} words of its own and is no more than {$site->max_similarity_percent}% similar to another page like it.")
                ->info()
                ->columnSpanFull(),
            Select::make('repair_type_id')
                ->label('Repair')
                ->relationship('repairType', 'name', fn (Builder $query) => $query->where('is_active', true)->orderBy('sort'))
                ->required()
                ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule): Unique => $rule->where('device_model_id', $this->getOwnerRecord()->getKey()))
                ->validationMessages(['unique' => 'This model already has a page for this repair.']),
            Toggle::make('is_enabled')
                ->label('Page is live')
                ->default(true),
            Textarea::make('intro')
                ->label('Introduction')
                ->rows(3)
                ->maxLength(1000)
                ->columnSpanFull(),
            ContentFields::blocks(),
        ]);
    }

    public function table(Table $table): Table
    {
        /** @var DeviceModel $model */
        $model = $this->getOwnerRecord();

        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('repairType'))
            ->columns([
                TextColumn::make('repairType.name')
                    ->label('Repair'),
                TextColumn::make('unique_word_count')
                    ->label('Own words')
                    ->numeric(),
                TextColumn::make('similarity_score')
                    ->label('Closest match')
                    ->suffix('%'),
                IconColumn::make('indexable')
                    ->label('Indexable')
                    ->state(fn (ModelRepairPage $record): bool => $record->passesContentChecks())
                    ->boolean()
                    ->tooltip('Search engines may index the page once its own content passes the checks.'),
                IconColumn::make('is_enabled')
                    ->label('Live')
                    ->boolean(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                Action::make('view')
                    ->label('View')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->color('gray')
                    ->url(fn (ModelRepairPage $record): string => route('repairs.model-repair', [$model->slug, $record->repairType->slug]), shouldOpenInNewTab: true)
                    ->visible(fn (ModelRepairPage $record): bool => $record->is_enabled),
                EditAction::make(),
                DeleteAction::make()->iconButton(),
            ]);
    }
}
