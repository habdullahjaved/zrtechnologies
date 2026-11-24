<x-layouts.app :seo="$seo">
    <x-site.page-header :title="$service->heading()" eyebrow="iPhone {{ $service->name }}" :breadcrumbs="$breadcrumbs">
        {{ $service->intro ?? $service->summary }}
        <div class="mt-7 flex flex-col gap-3 sm:flex-row sm:items-center">
            <x-ui.button :href="$bookingUrl" size="lg">
                <x-heroicon-s-wrench-screwdriver class="size-5" aria-hidden="true" />
                Book this repair
            </x-ui.button>
            @if ($fromPence !== null)
                <p class="text-white">
                    <span class="text-sm text-sky-200">From</span>
                    <span class="font-heading text-3xl font-extrabold">{{ \App\Support\Money::format($fromPence) }}</span>
                    <span class="text-sm text-sky-200">including parts and labour</span>
                </p>
            @endif
        </div>
    </x-site.page-header>

    <x-ui.section tone="white">
        <div class="grid gap-12 lg:grid-cols-[minmax(0,1fr)_22rem] lg:items-start">
            <div class="min-w-0">
                @if (count($content->tableOfContents()) >= 3)
                    <x-site.toc :headings="$content->tableOfContents()" class="mb-10" />
                @endif

                <x-content.prose :content="$content" />
            </div>

            <aside class="space-y-6 lg:sticky lg:top-28">
                <x-ui.card padding="p-6">
                    <h2 class="text-xl font-bold">Why book with us</h2>
                    <ul class="mt-4 space-y-3 text-[0.95rem]">
                        @foreach ([
                            ['icon' => 'heroicon-s-truck', 'text' => 'We collect from your home or work and bring it back'],
                            ['icon' => 'heroicon-s-shield-check', 'text' => 'Warranty on the part and our work'],
                            ['icon' => 'heroicon-s-currency-pound', 'text' => 'Fixed price before we start, nothing to pay today'],
                            ['icon' => 'heroicon-s-lock-closed', 'text' => 'We never ask for your passcode in a form'],
                        ] as $point)
                            <li class="flex gap-3">
                                <x-dynamic-component :component="$point['icon']" class="mt-0.5 size-5 shrink-0 text-royal-600" aria-hidden="true" />
                                {{ $point['text'] }}
                            </li>
                        @endforeach
                    </ul>
                    <x-ui.button :href="$bookingUrl" class="mt-6 w-full">Book this repair</x-ui.button>
                </x-ui.card>
                <x-ui.card padding="p-6">
                    <h2 class="text-xl font-bold">Check we collect from you</h2>
                    <div class="mt-4">
                        <livewire:postcode-checker variant="compact" id-prefix="service-postcode" />
                    </div>
                </x-ui.card>
            </aside>
        </div>
    </x-ui.section>

    @if ($priceRows->isNotEmpty())
        <x-ui.section tone="ice" id="prices" class="scroll-mt-20">
            <x-ui.section-heading eyebrow="Prices" title="iPhone {{ $service->name }} prices by model">
                The price you see is the price you pay, including parts, labour and collection in our core areas.
            </x-ui.section-heading>

            <div class="mx-auto mt-10 max-w-5xl overflow-x-auto rounded-card border border-ice-100 bg-white shadow-soft">
                <table class="w-full min-w-lg text-left text-[0.95rem]">
                    <caption class="sr-only">iPhone {{ $service->name }} prices for every model</caption>
                    <thead class="bg-navy-900 text-white">
                        <tr>
                            <th scope="col" class="px-5 py-3.5 font-semibold">Model</th>
                            <th scope="col" class="px-5 py-3.5 font-semibold">Price</th>
                            <th scope="col" class="px-5 py-3.5 font-semibold"><span class="sr-only">Book</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ice-100">
                        @foreach ($priceRows as ['model' => $model, 'prices' => $modelPrices])
                            <tr class="hover:bg-ice-50/60">
                                <th scope="row" class="px-5 py-3.5 font-semibold">
                                    <a href="{{ $model->url() }}" class="text-navy-900 hover:text-royal-600">{{ $model->name }}</a>
                                </th>
                                <td class="px-5 py-3.5">
                                    <ul class="space-y-1">
                                        @foreach ($modelPrices as $price)
                                            <li class="flex flex-wrap items-baseline gap-x-2">
                                                @if ($repairTypes->count() > 1)
                                                    <span>{{ $price->repairType->displayName() }}@if ($price->partQuality) ({{ $price->partQuality->name }})@endif:</span>
                                                @elseif ($price->partQuality)
                                                    <span>{{ $price->partQuality->name }}:</span>
                                                @endif
                                                <strong class="text-navy-900">{{ $price->formattedPrice() }}</strong>
                                                @if ($price->warrantyLabel())
                                                    <span class="text-sm">· {{ $price->warrantyLabel() }}</span>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                </td>
                                <td class="px-5 py-3.5 text-right">
                                    <a href="{{ app(\App\Support\BookingCallToAction::class)->url($model, $modelPrices->first()->repairType) }}" class="font-semibold whitespace-nowrap text-royal-600 hover:underline">Book <span class="sr-only">{{ $service->name }} for {{ $model->name }}</span></a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-ui.section>
    @endif

    @if ($videos->isNotEmpty())
        <x-ui.section tone="white">
            <x-ui.section-heading eyebrow="Watch" title="See how we do it" />
            <ul class="mx-auto mt-10 grid max-w-4xl grid-cols-2 gap-5 md:grid-cols-4">
                @foreach ($videos as $video)
                    <li><x-site.video-card :video="$video" /></li>
                @endforeach
            </ul>
        </x-ui.section>
    @endif

    @if ($faqs->isNotEmpty())
        <x-ui.section tone="white" width="narrow">
            <x-ui.section-heading eyebrow="FAQs" title="{{ $service->name }} questions" />
            <x-site.faq-list :faqs="$faqs" class="mt-10" />
        </x-ui.section>
    @endif

    @if ($posts->isNotEmpty())
        <x-ui.section tone="ice">
            <x-ui.section-heading eyebrow="Guides" title="Helpful reading" />
            <ul class="mt-12 grid gap-6 md:grid-cols-3">
                @foreach ($posts as $post)
                    <li><x-site.post-card :post="$post" /></li>
                @endforeach
            </ul>
        </x-ui.section>
    @endif

    @if ($otherServices->isNotEmpty())
        <x-ui.section tone="white" spacing="tight">
            <h2 class="text-center text-2xl font-extrabold">Other repairs we do</h2>
            <ul class="mt-8 flex flex-wrap justify-center gap-2.5">
                @foreach ($otherServices as $other)
                    <li><a href="{{ $other->url() }}" class="inline-flex rounded-full border border-ice-100 bg-ice-50 px-4 py-2 font-semibold text-navy-900 hover:border-sky-200">{{ $other->name }}</a></li>
                @endforeach
            </ul>
        </x-ui.section>
    @endif

    <x-site.contact-actions />
</x-layouts.app>
