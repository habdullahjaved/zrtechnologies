<x-layouts.app :seo="$seo">
    <x-site.page-header :title="$model->name.' Repair in Blackburn'" eyebrow="Repairs by model" :breadcrumbs="$breadcrumbs">
        {{ $model->intro ?? "Every {$model->name} repair we offer, with fixed prices. We collect your iPhone from your door, repair it with quality-tested parts and bring it back with a warranty." }}
        <div class="mt-7 flex flex-col gap-3 sm:flex-row sm:items-center">
            <x-ui.button :href="$bookingUrl" size="lg">
                <x-heroicon-s-wrench-screwdriver class="size-5" aria-hidden="true" />
                Book a {{ $model->name }} repair
            </x-ui.button>
            <p class="text-white">
                <span class="text-sm text-sky-200">Repairs from</span>
                <span class="font-heading text-3xl font-extrabold">{{ \App\Support\Money::format($fromPence) }}</span>
            </p>
        </div>
    </x-site.page-header>

    <x-ui.section tone="ice">
        <div class="grid gap-10 lg:grid-cols-[minmax(0,1fr)_22rem] lg:items-start">
            <div class="min-w-0">
                <h2 class="text-2xl font-extrabold sm:text-3xl">{{ $model->name }} repair prices</h2>
                <p class="mt-2">Prices include parts and labour. Choose a repair to book it with the model already filled in.</p>

                <ul class="mt-8 grid gap-4">
                    @foreach ($pricesByRepair as $repairPrices)
                        @php
                            $repair = $repairPrices->first()->repairType;
                            $service = $directory->forRepairType($repair);
                        @endphp
                        <li class="rounded-card border border-ice-100 bg-white p-5 shadow-soft sm:p-6">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div class="flex items-start gap-4">
                                    @if ($repair->icon)
                                        <img src="{{ asset("images/icons/icon-{$repair->icon}-navy-96.webp") }}" alt="" width="48" height="48" loading="lazy" class="size-12 shrink-0 rounded-xl bg-ice-50 p-1.5">
                                    @endif
                                    <div>
                                        <h3 class="text-lg font-bold">
                                            @if (in_array($repair->id, $repairPageSlugs, true))
                                                <a href="{{ route('repairs.model-repair', [$model->slug, $repair->slug]) }}" class="hover:text-royal-600">{{ $model->name }} {{ $repair->name }}</a>
                                            @else
                                                {{ $repair->name }}
                                            @endif
                                        </h3>
                                        @if ($repair->summary)
                                            <p class="mt-1 max-w-xl text-[0.95rem] leading-relaxed">{{ $repair->summary }}</p>
                                        @endif
                                        @if ($service)
                                            <a href="{{ $service->url() }}" class="mt-2 inline-block text-sm font-semibold text-royal-600 hover:underline">About {{ \Illuminate\Support\Str::lower($service->name) }}</a>
                                        @endif
                                    </div>
                                </div>
                                <div class="text-right">
                                    @foreach ($repairPrices as $price)
                                        <p class="whitespace-nowrap">
                                            @if ($price->partQuality)<span class="text-sm">{{ $price->partQuality->name }}</span>@endif
                                            <strong class="font-heading text-xl text-navy-900">{{ $price->formattedPrice() }}</strong>
                                        </p>
                                    @endforeach
                                    <a href="{{ app(\App\Support\BookingCallToAction::class)->url($model, $repair) }}" class="mt-2 inline-flex items-center gap-1 text-sm font-semibold text-royal-600 hover:underline">
                                        Book <span class="sr-only">{{ $repair->name }}</span>
                                        <x-heroicon-m-arrow-right class="size-4" aria-hidden="true" />
                                    </a>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>

            <aside class="space-y-6 lg:sticky lg:top-28">
                <x-ui.card padding="p-6">
                    <h2 class="text-xl font-bold">About the {{ $model->name }}</h2>
                    <dl class="mt-4 space-y-3 text-[0.95rem]">
                        @if ($model->release_year)
                            <div class="flex justify-between gap-4"><dt>Released</dt><dd class="font-semibold text-navy-900">{{ $model->release_year }}</dd></div>
                        @endif
                        @if ($model->display_type)
                            <div class="flex justify-between gap-4"><dt>Screen</dt><dd class="text-right font-semibold text-navy-900">{{ $model->display_type->getLabel() }}</dd></div>
                        @endif
                        <div class="flex justify-between gap-4"><dt>Unlock</dt><dd class="font-semibold text-navy-900">{{ $model->hasFaceId() ? 'Face ID' : 'Touch ID' }}</dd></div>
                    </dl>
                    <div class="mt-5 space-y-3 border-t border-ice-100 pt-5 text-[0.95rem] leading-relaxed">
                        @if ($model->display_type === \App\Enums\DisplayType::Oled)
                            <p>The {{ $model->name }} has an OLED screen. A cracked OLED often shows lines, green tints or black patches as well as broken glass, so it is best replaced early before the damage spreads.</p>
                        @elseif ($model->display_type === \App\Enums\DisplayType::Lcd)
                            <p>The {{ $model->name }} has an LCD screen, which is usually quicker and cheaper to replace than an OLED display.</p>
                        @endif
                        @if ($model->hasFaceId())
                            <p>Face ID is tied to the phone’s own sensors. We never replace them on a screen repair, so Face ID keeps working afterwards.</p>
                        @else
                            <p>The Home button is paired to the phone for Touch ID, so we always keep and refit your original button.</p>
                        @endif
                        @if ($model->notes)
                            <p>{{ $model->notes }}</p>
                        @endif
                    </div>
                </x-ui.card>
                <x-ui.card padding="p-6">
                    <h2 class="text-xl font-bold">Check we collect from you</h2>
                    <div class="mt-4">
                        <livewire:postcode-checker variant="compact" id-prefix="model-postcode" />
                    </div>
                </x-ui.card>
            </aside>
        </div>
    </x-ui.section>

    @unless ($content->isEmpty())
        <x-ui.section tone="white" width="narrow">
            <x-content.prose :content="$content" />
        </x-ui.section>
    @endunless

    @if ($faqs->isNotEmpty())
        <x-ui.section tone="white" width="narrow">
            <x-ui.section-heading eyebrow="FAQs" title="{{ $model->name }} repair questions" />
            <x-site.faq-list :faqs="$faqs" class="mt-10" />
        </x-ui.section>
    @endif

    @if ($relatedModels->isNotEmpty())
        <x-ui.section tone="ice" spacing="tight">
            <h2 class="text-center text-2xl font-extrabold">Repairs for other models</h2>
            <ul class="mt-8 flex flex-wrap justify-center gap-2.5">
                @foreach ($relatedModels as $other)
                    <li><a href="{{ $other->url() }}" class="inline-flex rounded-full border border-ice-100 bg-white px-4 py-2 font-semibold text-navy-900 hover:border-sky-200">{{ $other->name }}</a></li>
                @endforeach
            </ul>
        </x-ui.section>
    @endif

    <x-site.contact-actions />
</x-layouts.app>
