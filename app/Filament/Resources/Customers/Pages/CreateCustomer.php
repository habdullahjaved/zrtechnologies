<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomer extends CreateRecord
{
    protected static string $resource = CustomerResource::class;

    /**
     * Record when and how marketing consent was given.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if ((bool) ($data['marketing_consent'] ?? false)) {
            $data['marketing_consent_at'] = now();
            $data['marketing_consent_source'] = 'admin';
        }

        return $data;
    }
}
