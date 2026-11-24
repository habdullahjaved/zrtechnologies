<x-layouts.app :seo="$seo">
    <x-site.page-header :title="$page->title" :eyebrow="$page->eyebrow" :breadcrumbs="$breadcrumbs">
        {{ $page->intro }}
    </x-site.page-header>

    <x-ui.section tone="ice" spacing="tight">
        <livewire:price-checker id-prefix="prices-checker" />
    </x-ui.section>

    @if ($repairs !== [])
        <x-ui.section tone="white">
            <x-ui.section-heading eyebrow="At a glance" title="Starting prices by repair">
                The lowest price for each repair across all the models we fix.
            </x-ui.section-heading>

            <ul class="mt-12 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($repairs as ['repair' => $repair, 'from_pence' => $fromPence])
                    <li class="flex items-center gap-4 rounded-card bg-white p-5 shadow-soft ring-1 ring-ice-100">
                        @if ($repair->icon)
                            <img src="{{ asset("images/icons/icon-{$repair->icon}-navy-96.webp") }}" alt="" width="48" height="48" loading="lazy" class="size-12 shrink-0 rounded-xl bg-ice-50 p-1.5">
                        @endif
                        <div class="min-w-0 flex-1">
                            <h3 class="font-bold">{{ $repair->name }}</h3>
                            @if ($duration = $repair->typicalDuration())
                                <p class="text-sm">Typical repair time {{ $duration }}</p>
                            @endif
                        </div>
                        <p class="text-right">
                            <span class="block text-xs font-semibold tracking-wide text-ink uppercase">from</span>
                            <span class="font-heading text-xl font-extrabold text-navy-900">{{ $fromPence === 0 ? 'Free' : \App\Support\Money::format($fromPence) }}</span>
                        </p>
                    </li>
                @endforeach
            </ul>
        </x-ui.section>
    @endif

    @if ($models->isNotEmpty())
        <x-ui.section tone="ice">
            <x-ui.section-heading eyebrow="Full price list" title="Every model, every repair">
                Tap a model to see its prices.
            </x-ui.section-heading>

            <div class="mx-auto mt-12 max-w-4xl divide-y divide-ice-100 overflow-hidden rounded-card bg-white shadow-soft ring-1 ring-ice-100">
                @foreach ($models as $deviceModel)
                    <details class="group">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-5 py-4 font-semibold text-navy-900 hover:bg-ice-50 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-sky-400 sm:px-6 [&::-webkit-details-marker]:hidden">
                            <span>{{ $deviceModel->name }} <span class="sr-only">repair prices</span></span>
                            <x-heroicon-m-chevron-down class="size-5 shrink-0 text-royal-600 transition group-open:rotate-180" aria-hidden="true" />
                        </summary>
                        <div class="px-5 pb-5 sm:px-6">
                            {{-- Cell styles are set once on the table to keep this long list light. --}}
                            <table class="w-full text-left text-sm [&_tbody_td]:py-2.5 [&_tbody_th]:py-2.5 [&_tbody_th]:pr-4 [&_tbody_th]:font-medium [&_tbody_th]:text-navy-900 [&_td:last-child]:text-right [&_td:last-child]:font-semibold [&_td:last-child]:whitespace-nowrap [&_td:last-child]:text-navy-900 [&_th:last-child]:text-right">
                                <caption class="sr-only">{{ $deviceModel->name }} repair prices</caption>
                                <thead>
                                    <tr class="border-b border-ice-100 text-xs tracking-wide text-ink uppercase">
                                        <th scope="col" class="py-2 pr-4 font-semibold">Repair</th>
                                        <th scope="col" class="py-2 pr-4 font-semibold">Warranty</th>
                                        <th scope="col" class="py-2 font-semibold">Price</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-ice-100">
@foreach ($priceList[$deviceModel->id] ?? [] as $repairPrices)
@foreach ($repairPrices as $price)
<tr><th scope="row">{{ $price->repairType->name }}@if ($price->partQuality) ({{ $price->partQuality->name }})@endif</th><td>{{ $price->warrantyLabel() ?? '—' }}</td><td>@if ($price->repairType->requires_diagnosis)from @endif{{ $price->effectivePricePence() === 0 ? 'Free' : $price->formattedPrice() }}</td></tr>
@endforeach
@endforeach
                                </tbody>
                            </table>
                        </div>
                    </details>
                @endforeach
            </div>
        </x-ui.section>
    @endif

    <x-ui.section tone="white">
        <div class="mx-auto grid max-w-5xl gap-8 lg:grid-cols-[1fr_1.4fr] lg:items-start">
            <x-ui.section-heading eyebrow="Can’t see your repair?" title="Ask us for a quote" align="left">
                Tell us your model and what is wrong. We will reply with a price, usually within a few hours, day or night.
            </x-ui.section-heading>
            <x-ui.card id="quote" class="scroll-mt-28">
                <livewire:enquiry-form type="quote" id-prefix="prices-quote" />
            </x-ui.card>
        </div>
    </x-ui.section>

    <x-site.contact-actions />
</x-layouts.app>
