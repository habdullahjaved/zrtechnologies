<?php

namespace App\Livewire;

use App\Models\DeviceModel;
use App\Models\RepairPrice;
use App\Services\Pricing\PricingService;
use App\Support\BookingCallToAction;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Instant price checker: pick an iPhone model, see every repair and its price.
 * Prices come from the database via PricingService.
 *
 * @property-read Collection<int, DeviceModel> $models
 * @property-read DeviceModel|null $selectedModel
 * @property-read SupportCollection<int, Collection<int, RepairPrice>> $prices
 */
class PriceChecker extends Component
{
    /** Selected model slug. */
    public string $model = '';

    /** HTML id prefix, so the checker can appear more than once on a page. */
    #[Locked]
    public string $idPrefix = 'price-checker';

    public function mount(?string $model = null, string $idPrefix = 'price-checker'): void
    {
        $this->idPrefix = $idPrefix;
        $requested = $model ?? request()->string('model')->toString();

        $this->model = $requested !== '' && $this->models->contains('slug', $requested) ? $requested : '';
    }

    public function selectModel(string $slug): void
    {
        $this->model = $this->models->contains('slug', $slug) ? $slug : '';
    }

    public function clearModel(): void
    {
        $this->model = '';
    }

    /**
     * @return Collection<int, DeviceModel>
     */
    #[Computed]
    public function models(): Collection
    {
        return app(PricingService::class)->modelsWithPrices();
    }

    #[Computed]
    public function selectedModel(): ?DeviceModel
    {
        return $this->model === '' ? null : $this->models->firstWhere('slug', $this->model);
    }

    /**
     * @return SupportCollection<int, Collection<int, RepairPrice>>
     */
    #[Computed]
    public function prices(): SupportCollection
    {
        $model = $this->selectedModel;

        return $model === null ? collect() : app(PricingService::class)->pricesForModel($model);
    }

    public function bookingUrl(RepairPrice $price): string
    {
        return app(BookingCallToAction::class)->url($this->selectedModel, $price->repairType, $price->partQuality);
    }

    public function render(BookingCallToAction $booking): View
    {
        return view('livewire.price-checker', [
            'modelsByFamily' => $this->models->groupBy(fn (DeviceModel $model): string => $model->family ?? 'Other models'),
            'popularModels' => $this->models->where('is_popular', true)->take(8),
            'bookingOpensWhatsApp' => $booking->opensWhatsApp(),
        ]);
    }
}
