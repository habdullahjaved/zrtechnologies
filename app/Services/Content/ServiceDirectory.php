<?php

namespace App\Services\Content;

use App\Models\RepairType;
use App\Models\Service;
use App\Services\Pricing\PricingService;
use Illuminate\Database\Eloquent\Collection;

/**
 * The iPhone repair service pages, with their "from" prices and which repair
 * types they cover. Used by the mega menu, footer, home page, pillar page and
 * model pages, and loaded once per request.
 */
class ServiceDirectory
{
    /** @var Collection<int, Service>|null */
    private ?Collection $services = null;

    /** @var array<int, int>|null */
    private ?array $fromPrices = null;

    public function __construct(private readonly PricingService $pricing) {}

    /**
     * Service pages customers can visit, in display order.
     *
     * @return Collection<int, Service>
     */
    public function services(): Collection
    {
        return $this->services ??= Service::query()
            ->listed()
            ->with(['repairTypes' => fn ($query) => $query->where('is_active', true)])
            ->get();
    }

    /**
     * The service page that covers a repair type, if any.
     */
    public function forRepairType(RepairType|int $repairType): ?Service
    {
        $id = $repairType instanceof RepairType ? $repairType->getKey() : $repairType;

        return $this->services()->first(
            fn (Service $service): bool => $service->repairTypes->contains('id', $id),
        );
    }

    /**
     * The lowest price across the service's repairs, in pence.
     */
    public function fromPrice(Service $service): ?int
    {
        $this->fromPrices ??= $this->pricing->fromPrices();

        $prices = $service->repairTypes
            ->map(fn (RepairType $repair): ?int => $this->fromPrices[$repair->getKey()] ?? null)
            ->filter();

        return $prices->isEmpty() ? null : (int) $prices->min();
    }
}
