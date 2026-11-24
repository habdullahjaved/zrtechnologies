<?php

namespace App\Filament\Resources\RepairJobs\Pages;

use App\Filament\Resources\RepairJobs\RepairJobResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRepairJobs extends ListRecords
{
    protected static string $resource = RepairJobResource::class;

    public function getSubheading(): ?string
    {
        return RepairJobResource::isOfficeUser()
            ? 'Jobs in progress are shown first; change the status filter to see returned, completed and cancelled jobs.'
            : 'The jobs assigned to you.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('New repair job'),
        ];
    }
}
