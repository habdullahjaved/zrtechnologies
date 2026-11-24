<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use App\Models\Customer;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

/**
 * @property-read Customer $record
 */
class EditCustomer extends EditRecord
{
    protected static string $resource = CustomerResource::class;

    public function getTitle(): string
    {
        return $this->record->full_name;
    }

    /**
     * Record when and how marketing consent changed.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ((bool) ($data['marketing_consent'] ?? false) !== $this->record->marketing_consent) {
            $data['marketing_consent_at'] = now();
            $data['marketing_consent_source'] = 'admin';
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->hidden(fn (): bool => $this->record->repairJobs()->exists())
                ->modalDescription('The customer is removed from lists and search; their enquiries are kept. Customers with repair jobs cannot be deleted.'),
        ];
    }
}
