<?php

namespace App\Filament\Resources\Faqs;

use App\Enums\FaqTopic;
use App\Filament\NavigationGroup;
use App\Filament\Resources\Faqs\Pages\ManageFaqs;
use App\Models\Faq;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

/**
 * FAQs for /faqs and for any page they are attached to.
 */
class FaqResource extends Resource
{
    protected static ?string $model = Faq::class;

    protected static ?string $modelLabel = 'FAQ';

    protected static ?string $pluralModelLabel = 'FAQs';

    protected static ?string $navigationLabel = 'FAQs';

    protected static ?string $recordTitleAttribute = 'question';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQuestionMarkCircle;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Content;

    protected static ?int $navigationSort = 40;

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            Select::make('topic')
                ->options(FaqTopic::class)
                ->required()
                ->native(false),
            TextInput::make('sort')
                ->label('Sort order')
                ->integer()
                ->minValue(0)
                ->default(fn (): int => (int) Faq::query()->max('sort') + 10)
                ->required(),
            TextInput::make('question')
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),
            Textarea::make('answer')
                ->helperText('Plain text. Leave a blank line between paragraphs. Keep it accurate: answers appear in Google search results.')
                ->required()
                ->rows(5)
                ->maxLength(3000)
                ->columnSpanFull(),
            Toggle::make('show_on_faq_page')
                ->label('Show on the FAQs page')
                ->default(true),
            Toggle::make('is_active')
                ->label('Active')
                ->helperText('Inactive FAQs are hidden everywhere.')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->reorderable('sort')
            ->columns([
                TextColumn::make('question')
                    ->description(fn (Faq $record): string => str($record->answer)->limit(110)->toString())
                    ->wrap()
                    ->searchable(['question', 'answer']),
                TextColumn::make('topic')
                    ->badge()
                    ->color('gray'),
                IconColumn::make('show_on_faq_page')
                    ->label('FAQs page')
                    ->boolean(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('topic')->options(FaqTopic::class),
                TernaryFilter::make('is_active')->label('Active'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->iconButton(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageFaqs::route('/'),
        ];
    }
}
