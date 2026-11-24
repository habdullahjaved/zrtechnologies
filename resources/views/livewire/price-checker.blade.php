@php
    $selected = $this->selectedModel;
@endphp

<div id="{{ $idPrefix }}" class="scroll-mt-28">
    <x-ui.card padding="p-5 sm:p-8">
        <div class="grid gap-4 sm:grid-cols-[1fr_auto] sm:items-end">
            <div>
                <label for="{{ $idPrefix }}-model" class="mb-1.5 block text-sm font-semibold text-navy-900">
                    Which iPhone do you have?
                </label>
                <select
                    id="{{ $idPrefix }}-model"
                    wire:model.live="model"
                    class="block w-full rounded-xl border border-slate-300 bg-white py-3 pr-10 pl-4 text-base text-navy-950 shadow-sm focus:border-royal-600 focus:ring-2 focus:ring-sky-400/40"
                >
                    <option value="">Choose your model…</option>
                    @foreach ($modelsByFamily as $family => $familyModels)
                        <optgroup label="{{ $family }}">
                            @foreach ($familyModels as $deviceModel)
                                <option value="{{ $deviceModel->slug }}">{{ $deviceModel->name }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
            </div>
            <p class="flex items-center gap-2 text-sm text-ink sm:pb-3">
                <x-heroicon-o-information-circle class="size-5 shrink-0 text-royal-600" aria-hidden="true" />
                Find it in Settings › General › About.
            </p>
        </div>

        @if (! $selected && $popularModels->isNotEmpty())
            <div class="mt-5">
                <p class="text-sm font-semibold text-navy-900">Popular models</p>
                <ul class="mt-2.5 flex flex-wrap gap-2">
                    @foreach ($popularModels as $deviceModel)
                        <li>
                            <button
                                type="button"
                                wire:click="selectModel('{{ $deviceModel->slug }}')"
                                class="rounded-full border border-ice-100 bg-ice-50 px-3.5 py-1.5 text-sm font-semibold text-royal-700 transition hover:border-sky-200 hover:bg-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-400"
                            >{{ $deviceModel->name }}</button>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div wire:loading.delay class="mt-6 text-sm font-medium text-royal-700" role="status">
            Loading prices…
        </div>

        <div wire:loading.remove aria-live="polite">
            @if ($selected)
                <div class="mt-8 flex flex-wrap items-baseline justify-between gap-3 border-t border-ice-100 pt-6">
                    <h3 class="text-xl font-bold sm:text-2xl">{{ $selected->name }} repair prices</h3>
                    <button type="button" wire:click="clearModel" class="text-sm font-semibold text-royal-600 underline-offset-4 hover:underline">
                        Choose a different model
                    </button>
                </div>

                <ul class="mt-5 grid gap-4 lg:grid-cols-2">
                    @foreach ($this->prices as $repairPrices)
                        @php
                            $repair = $repairPrices->first()->repairType;
                        @endphp
                        <li wire:key="{{ $idPrefix }}-repair-{{ $repair->id }}" class="flex flex-col rounded-2xl border border-ice-100 bg-white p-5 shadow-sm">
                            <div class="flex gap-4">
                                @if ($repair->icon)
                                    <img
                                        src="{{ asset("images/icons/icon-{$repair->icon}-navy-96.webp") }}"
                                        alt=""
                                        width="48"
                                        height="48"
                                        loading="lazy"
                                        class="size-12 shrink-0 rounded-xl bg-ice-50 p-1.5"
                                    >
                                @endif
                                <div class="min-w-0">
                                    <h4 class="text-lg font-bold">{{ $repair->name }}</h4>
                                    @if ($repair->summary)
                                        <p class="mt-1 text-sm leading-relaxed">{{ $repair->summary }}</p>
                                    @endif
                                </div>
                            </div>

                            <ul class="mt-4 flex flex-1 flex-col justify-end gap-2.5">
                                @foreach ($repairPrices as $price)
                                    <li class="flex flex-wrap items-center justify-between gap-3 rounded-xl bg-ice-50 px-4 py-3">
                                        <div class="min-w-0">
                                            @if ($price->partQuality)
                                                <p class="font-semibold text-navy-900">{{ $price->partQuality->name }}</p>
                                            @endif
                                            <p class="text-sm">
                                                @if ($warranty = $price->warrantyLabel())
                                                    {{ $warranty }} warranty
                                                @elseif ($repair->requires_diagnosis)
                                                    Final price confirmed after diagnosis
                                                @endif
                                                @unless ($price->in_stock)
                                                    <span class="text-warning-700"> · Part to order</span>
                                                @endunless
                                            </p>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <p class="text-right">
                                                @if ($price->isOnSale())
                                                    <del class="mr-1 text-sm text-ink">{{ \App\Support\Money::format($price->price_pence) }}</del>
                                                @endif
                                                @if ($repair->requires_diagnosis)
                                                    <span class="text-sm">from</span>
                                                @endif
                                                <span class="font-heading text-xl font-extrabold text-navy-900">
                                                    {{ $price->effectivePricePence() === 0 ? 'Free' : $price->formattedPrice() }}
                                                </span>
                                            </p>
                                            <x-ui.button
                                                :href="$this->bookingUrl($price)"
                                                size="sm"
                                                :external="$bookingOpensWhatsApp"
                                                aria-label="Book {{ $repair->name }}{{ $price->partQuality ? ' ('.$price->partQuality->name.')' : '' }} for {{ $selected->name }}"
                                            >Book</x-ui.button>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </li>
                    @endforeach
                </ul>

                <p class="mt-6 text-sm text-ink">
                    Prices include parts and labour. A collection fee may apply depending on where you live. Not sure what is wrong? Choose Diagnostics and we will give you a fixed quote before any repair.
                </p>
            @endif
        </div>
    </x-ui.card>
</div>
