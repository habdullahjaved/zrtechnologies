<?php

namespace App\Filament\Resources\Leads\Pages;

use App\Filament\Resources\Leads\LeadActions;
use App\Filament\Resources\Leads\LeadResource;
use App\Models\Lead;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

/**
 * @property-read Lead $record
 */
class ViewLead extends ViewRecord
{
    protected static string $resource = LeadResource::class;

    public function getTitle(): string
    {
        return $this->record->reference.' · '.$this->record->displayName();
    }

    protected function getHeaderActions(): array
    {
        return [
            LeadActions::call(),
            LeadActions::whatsApp(),
            LeadActions::email(),
            LeadActions::openJob(),
            LeadActions::convertToJob(),
            ActionGroup::make([
                LeadActions::changeStatus(),
                LeadActions::assignToMe(),
                EditAction::make(),
            ])->button()->label('More')->color('gray'),
        ];
    }
}
