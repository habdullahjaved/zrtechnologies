<?php

namespace App\Filament\Resources\RepairTypes\Pages;

use App\Filament\Resources\RepairTypes\RepairTypeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRepairType extends EditRecord
{
    protected static string $resource = RepairTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
