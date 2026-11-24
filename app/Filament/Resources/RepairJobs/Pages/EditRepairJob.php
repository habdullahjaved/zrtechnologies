<?php

namespace App\Filament\Resources\RepairJobs\Pages;

use App\Filament\Resources\RepairJobs\RepairJobResource;
use App\Models\AvailabilityWindow;
use App\Models\RepairJob;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

/**
 * @property-read RepairJob $record
 */
class EditRepairJob extends EditRecord
{
    protected static string $resource = RepairJobResource::class;

    public function getTitle(): string
    {
        return 'Edit '.$this->record->reference;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (array_key_exists('availability_window_id', $data)) {
            $window = filled($data['availability_window_id']) ? AvailabilityWindow::query()->find($data['availability_window_id']) : null;
            $data['collection_window'] = $window?->timeRange();
        }

        return $data;
    }

    /**
     * The quote always matches the repairs listed on the job.
     */
    protected function afterSave(): void
    {
        if (RepairJobResource::canViewFinancials()) {
            $this->record->update(['quoted_price_pence' => (int) $this->record->items()->sum('price_pence')]);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return RepairJobResource::getUrl('view', ['record' => $this->record]);
    }
}
