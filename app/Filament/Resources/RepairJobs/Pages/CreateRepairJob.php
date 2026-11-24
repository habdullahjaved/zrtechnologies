<?php

namespace App\Filament\Resources\RepairJobs\Pages;

use App\Enums\JobStatus;
use App\Filament\Resources\RepairJobs\RepairJobResource;
use App\Models\AvailabilityWindow;
use App\Models\JobStatusChange;
use App\Models\RepairJob;
use App\Settings\BookingSettings;
use App\Support\ReferenceGenerator;
use Filament\Resources\Pages\CreateRecord;

/**
 * A job booked by staff (phone or walk-up), rather than through the website.
 */
class CreateRepairJob extends CreateRecord
{
    protected static string $resource = RepairJobResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $window = filled($data['availability_window_id'] ?? null) ? AvailabilityWindow::query()->find($data['availability_window_id']) : null;

        return [
            ...$data,
            'reference' => app(ReferenceGenerator::class)->next(ReferenceGenerator::JOB, app(BookingSettings::class)->reference_prefix),
            'status' => JobStatus::Booked,
            'collection_window' => $window?->timeRange(),
            'booked_at' => now(),
        ];
    }

    protected function afterCreate(): void
    {
        /** @var RepairJob $job */
        $job = $this->getRecord();
        $job->update(['quoted_price_pence' => (int) $job->items()->sum('price_pence')]);

        JobStatusChange::query()->create([
            'repair_job_id' => $job->getKey(),
            'to_status' => JobStatus::Booked,
            'note' => 'Booked by staff.',
            'user_id' => auth()->id(),
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return RepairJobResource::getUrl('view', ['record' => $this->getRecord()]);
    }
}
