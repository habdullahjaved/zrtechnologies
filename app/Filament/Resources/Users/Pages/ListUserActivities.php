<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use pxlrbt\FilamentActivityLog\Pages\ListActivitiesBySubject;

class ListUserActivities extends ListActivitiesBySubject
{
    protected static string $resource = UserResource::class;
}
