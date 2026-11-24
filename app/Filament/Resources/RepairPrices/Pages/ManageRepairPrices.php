<?php

namespace App\Filament\Resources\RepairPrices\Pages;

use App\Actions\Pricing\ExportRepairPrices;
use App\Actions\Pricing\ImportRepairPrices;
use App\Filament\Resources\RepairPrices\RepairPriceResource;
use App\Models\RepairPrice;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Icons\Heroicon;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ManageRepairPrices extends ManageRecords
{
    protected static string $resource = RepairPriceResource::class;

    public function getSubheading(): ?string
    {
        $drafts = RepairPrice::query()->where('is_draft', true)->count();

        return $drafts > 0
            ? "{$drafts} ".str('price')->plural($drafts).' still marked DRAFT. Check each one, then select them and choose “Confirm prices”.'
            : 'Every price is confirmed. Changes appear on the website straight away.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Add price'),
            ActionGroup::make([
                Action::make('export')
                    ->label('Download CSV')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->action(fn (ExportRepairPrices $export): StreamedResponse => response()->streamDownload(
                        function () use ($export): void {
                            $stream = fopen('php://output', 'wb');

                            if ($stream !== false) {
                                $export->handle($stream);
                                fclose($stream);
                            }
                        },
                        $export->filename(),
                        ['Content-Type' => 'text/csv; charset=UTF-8'],
                    )),
                Action::make('import')
                    ->label('Upload CSV')
                    ->icon(Heroicon::OutlinedArrowUpTray)
                    ->visible(fn (): bool => RepairPriceResource::canUpdateAny() && RepairPriceResource::canCreate())
                    ->modalHeading('Update prices from a CSV file')
                    ->modalDescription('Download the CSV, change prices in Excel or Google Sheets, then upload it here. Rows are matched on model_slug, repair_slug and quality_slug. If any row has a problem, nothing is changed.')
                    ->modalSubmitActionLabel('Import')
                    ->schema([
                        FileUpload::make('file')
                            ->label('CSV file')
                            ->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel'])
                            ->maxSize(2048)
                            ->storeFiles(false)
                            ->required(),
                    ])
                    ->action(function (array $data, ImportRepairPrices $import): void {
                        /** @var TemporaryUploadedFile $file */
                        $file = $data['file'];
                        $result = $import->handle($file->getRealPath());

                        if ($result->failed()) {
                            Notification::make()
                                ->title('Nothing was imported')
                                ->body(implode("\n", $result->errors))
                                ->danger()
                                ->persistent()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title('Prices imported')
                            ->body($result->summary())
                            ->success()
                            ->send();
                    }),
            ])
                ->label('CSV')
                ->icon(Heroicon::OutlinedTableCells)
                ->button()
                ->color('gray'),
        ];
    }
}
