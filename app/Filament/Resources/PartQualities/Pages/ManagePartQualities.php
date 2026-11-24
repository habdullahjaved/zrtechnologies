<?php

namespace App\Filament\Resources\PartQualities\Pages;

use App\Filament\Resources\PartQualities\PartQualityResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManagePartQualities extends ManageRecords
{
    protected static string $resource = PartQualityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
