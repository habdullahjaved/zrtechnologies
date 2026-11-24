<?php

namespace App\Filament\Resources\NotFoundLogs\Pages;

use App\Filament\Resources\NotFoundLogs\NotFoundLogResource;
use Filament\Resources\Pages\ListRecords;

class ListNotFoundLogs extends ListRecords
{
    protected static string $resource = NotFoundLogResource::class;

    public function getSubheading(): ?string
    {
        return 'Addresses visitors tried that do not exist. Redirect the popular ones to the right page.';
    }
}
