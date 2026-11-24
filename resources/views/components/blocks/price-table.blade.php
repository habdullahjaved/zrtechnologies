@php
    $repair = filled($data['repair_type_id'] ?? null) ? \App\Models\RepairType::query()->listed()->find($data['repair_type_id']) : null;
    $prices = $repair
        ? \App\Models\RepairPrice::query()->available()->where('repair_type_id', $repair->id)
            ->whereHas('deviceModel', fn ($models) => $models->where('is_active', true))
            ->with(['deviceModel', 'partQuality'])->get()
            ->sortBy(fn ($price) => [$price->deviceModel->sort, $price->partQuality->sort ?? 0])
            ->groupBy('device_model_id')
        : collect();
@endphp

@if ($repair && $prices->isNotEmpty())
    <div class="not-prose my-8 overflow-x-auto rounded-card border border-ice-100">
        <table class="w-full min-w-md text-left text-[0.95rem]">
            <caption class="bg-ice-50 px-4 py-3 text-left font-heading font-bold text-navy-900">{{ filled($data['title'] ?? null) ? $data['title'] : $repair->name.' prices' }}</caption>
            <thead class="bg-navy-900 text-white">
                <tr>
                    <th scope="col" class="px-4 py-3 font-semibold">Model</th>
                    <th scope="col" class="px-4 py-3 font-semibold">Price</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-ice-100 bg-white">
                @foreach ($prices as $modelPrices)
                    <tr>
                        <th scope="row" class="px-4 py-3 font-semibold text-navy-900">{{ $modelPrices->first()->deviceModel->name }}</th>
                        <td class="px-4 py-3">
                            @foreach ($modelPrices as $price)
                                <span class="mr-4 inline-block whitespace-nowrap">@if ($price->partQuality){{ $price->partQuality->name }}: @endif<strong class="text-navy-900">{{ $price->formattedPrice() }}</strong></span>
                            @endforeach
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
