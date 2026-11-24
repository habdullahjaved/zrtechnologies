<?php

namespace App\Filament\Resources\AvailabilityOverrides\Pages;

use App\Filament\Resources\AvailabilityOverrides\AvailabilityOverrideResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageAvailabilityOverrides extends ManageRecords
{
    protected static string $resource = AvailabilityOverrideResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
