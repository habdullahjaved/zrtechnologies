<?php

namespace App\Filament\Resources\MessageTemplates\Pages;

use App\Filament\Resources\MessageTemplates\MessageTemplateResource;
use App\Models\MessageTemplate;
use App\Support\Messaging\MessageTemplateCatalogue;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

/**
 * @property-read MessageTemplate $record
 */
class EditMessageTemplate extends EditRecord
{
    protected static string $resource = MessageTemplateResource::class;

    public function getTitle(): string
    {
        return $this->record->name;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('restoreDefault')
                ->label('Restore original wording')
                ->icon(Heroicon::OutlinedArrowUturnLeft)
                ->color('gray')
                ->requiresConfirmation()
                ->modalDescription('Replaces the subject and message with the wording this template started with.')
                ->visible(fn (): bool => isset(MessageTemplateCatalogue::all()[$this->record->key]))
                ->action(function (): void {
                    $default = MessageTemplateCatalogue::all()[$this->record->key];
                    $this->record->update(['subject' => $default['subject'], 'body' => $default['body']]);
                    $this->fillForm();

                    Notification::make()->success()->title('Original wording restored')->send();
                }),
        ];
    }
}
