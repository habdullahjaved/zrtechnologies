<?php

namespace App\Filament\Resources\MessageTemplates;

use App\Enums\MessageChannel;
use App\Filament\NavigationGroup;
use App\Filament\Resources\MessageTemplates\Pages\EditMessageTemplate;
use App\Filament\Resources\MessageTemplates\Pages\ListMessageTemplates;
use App\Models\MessageTemplate;
use App\Support\Messaging\MessageTemplateCatalogue;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use UnitEnum;

/**
 * The wording of customer emails, team alerts and WhatsApp replies.
 * Templates are created by the seeder; staff edit them but cannot add or delete them.
 */
class MessageTemplateResource extends Resource
{
    protected static ?string $model = MessageTemplate::class;

    protected static ?string $modelLabel = 'message template';

    protected static ?string $navigationLabel = 'Message templates';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Settings;

    protected static ?int $navigationSort = 60;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make(fn (?MessageTemplate $record): string => $record->name ?? 'Template')
                    ->description(fn (?MessageTemplate $record): ?string => $record?->description)
                    ->columnSpan(2)
                    ->schema([
                        TextInput::make('subject')
                            ->label('Email subject')
                            ->maxLength(200)
                            ->required(fn (?MessageTemplate $record): bool => $record?->channel !== MessageChannel::WhatsApp)
                            ->visible(fn (?MessageTemplate $record): bool => $record?->channel !== MessageChannel::WhatsApp),
                        Textarea::make('body')
                            ->label('Message')
                            ->helperText(fn (?MessageTemplate $record): string => $record?->channel === MessageChannel::WhatsApp
                                ? 'Plain text. Keep it short and friendly.'
                                : 'Markdown: # for a heading, **bold**, - for a bullet. A line whose placeholders are all empty is left out.')
                            ->rows(16)
                            ->required()
                            ->maxLength(10000),
                        Toggle::make('is_active')
                            ->label('In use')
                            ->helperText('Switch off to stop sending this message.'),
                    ]),
                Section::make('Placeholders')
                    ->description('Type these exactly, including the curly brackets.')
                    ->columnSpan(1)
                    ->schema([
                        Text::make(fn (?MessageTemplate $record): HtmlString => self::placeholderList($record)),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('key')
            ->columns([
                TextColumn::make('name')
                    ->description(fn (MessageTemplate $record): ?string => $record->description)
                    ->searchable()
                    ->wrap(),
                TextColumn::make('channel')
                    ->label('Used for')
                    ->badge(),
                IconColumn::make('is_active')
                    ->label('In use')
                    ->boolean(),
                TextColumn::make('updated_at')
                    ->label('Last edited')
                    ->since()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('channel')
                    ->label('Used for')
                    ->options(MessageChannel::class),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->paginated(false);
    }

    private static function placeholderList(?MessageTemplate $record): HtmlString
    {
        $definition = $record !== null ? (MessageTemplateCatalogue::all()[$record->key] ?? null) : null;
        $variables = [...($definition['variables'] ?? []), ...MessageTemplateCatalogue::COMMON_VARIABLES];

        $items = collect($variables)
            ->map(fn (string $description, string $name): string => '<li><code class="font-mono text-xs font-semibold">{'.e($name).'}</code><br><span class="text-sm text-gray-500 dark:text-gray-400">'.e($description).'</span></li>')
            ->implode('');

        return new HtmlString('<ul class="grid gap-2">'.$items.'</ul>');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMessageTemplates::route('/'),
            'edit' => EditMessageTemplate::route('/{record}/edit'),
        ];
    }
}
