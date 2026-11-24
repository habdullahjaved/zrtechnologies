<?php

namespace App\Filament\Resources\Activities\Pages;

use App\Filament\Resources\Activities\ActivityResource;
use Filament\Resources\Pages\ListRecords;

class ListActivities extends ListRecords
{
    protected static string $resource = ActivityResource::class;

    public function getSubheading(): ?string
    {
        return 'Every change made in the admin, newest first. Entries are kept for 12 months.';
    }
}
