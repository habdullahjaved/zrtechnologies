<?php

namespace App\Filament\Resources\Leads\Pages;

use App\Filament\Resources\Leads\LeadResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLeads extends ListRecords
{
    protected static string $resource = LeadResource::class;

    public function getSubheading(): ?string
    {
        return 'Every booking and enquiry, with where it came from. Open leads are shown first; change the status filter to see the rest.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Log an enquiry'),
        ];
    }
}
