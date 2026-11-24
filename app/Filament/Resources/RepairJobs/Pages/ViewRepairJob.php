<?php

namespace App\Filament\Resources\RepairJobs\Pages;

use App\Filament\Resources\RepairJobs\RepairJobActions;
use App\Filament\Resources\RepairJobs\RepairJobResource;
use App\Models\RepairJob;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

/**
 * @property-read RepairJob $record
 */
class ViewRepairJob extends ViewRecord
{
    protected static string $resource = RepairJobResource::class;

    public function getTitle(): string
    {
        return $this->record->reference;
    }

    public function getSubheading(): ?string
    {
        return collect([$this->record->deviceModel?->name, $this->record->customer->full_name])->filter()->implode(' · ');
    }

    protected function getHeaderActions(): array
    {
        return [
            RepairJobActions::changeStatus(),
            RepairJobActions::call(),
            RepairJobActions::whatsApp(),
            ActionGroup::make([
                RepairJobActions::assignTechnician(),
                RepairJobActions::directions(),
                RepairJobActions::trackingPage(),
                EditAction::make(),
            ])->button()->label('More')->color('gray'),
        ];
    }
}
