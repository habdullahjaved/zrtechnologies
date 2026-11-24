<?php

namespace App\Filament\Resources\Reviews\Pages;

use App\Filament\Resources\Reviews\ReviewResource;
use App\Settings\SocialSettings;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Icons\Heroicon;

class ManageReviews extends ManageRecords
{
    protected static string $resource = ReviewResource::class;

    protected function getHeaderActions(): array
    {
        $profile = app(SocialSettings::class)->google_business_profile;

        return [
            Action::make('google')
                ->label('Open Google profile')
                ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->color('gray')
                ->url($profile, shouldOpenInNewTab: true)
                ->visible(filled($profile)),
            CreateAction::make()->label('Add a review'),
        ];
    }
}
