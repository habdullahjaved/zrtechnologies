<x-layouts.app :seo="$seo">
    <x-site.page-header :title="'iPhone Repair in '.$area->town" eyebrow="Collection and delivery in {{ $area->town }}" :breadcrumbs="$breadcrumbs">
        {{ $area->intro ?? "We collect your iPhone from your home or workplace in {$area->town}, repair it with quality-tested parts and bring it back, with a warranty on every repair." }}
        <div class="mt-7 flex flex-col gap-3 sm:flex-row">
            <x-ui.button :href="route('booking.create')" size="lg">
                <x-heroicon-s-wrench-screwdriver class="size-5" aria-hidden="true" />
                Book a collection in {{ $area->town }}
            </x-ui.button>
        </div>
    </x-site.page-header>

    <x-ui.section tone="ice" spacing="tight">
        <div class="grid gap-6 lg:grid-cols-3">
            <x-ui.card padding="p-6">
                <x-heroicon-o-map-pin class="size-7 text-royal-600" aria-hidden="true" />
                <h2 class="mt-3 text-lg font-bold">Postcodes we cover</h2>
                <ul class="mt-3 flex flex-wrap gap-1.5">
                    @foreach ($area->postcode_districts as $district)
                        <li><x-ui.badge>{{ $district }}</x-ui.badge></li>
                    @endforeach
                </ul>
                @if ($area->summary)
                    <p class="mt-3 text-[0.95rem] leading-relaxed">{{ $area->summary }}</p>
                @endif
            </x-ui.card>
            <x-ui.card padding="p-6">
                <x-heroicon-o-clock class="size-7 text-royal-600" aria-hidden="true" />
                <h2 class="mt-3 text-lg font-bold">When we collect</h2>
                <p class="mt-3 text-[0.95rem] leading-relaxed">{{ $area->typical_collection_times ?? 'Choose a collection window that suits you when you book.' }}</p>
            </x-ui.card>
            <x-ui.card padding="p-6">
                <x-heroicon-o-truck class="size-7 text-royal-600" aria-hidden="true" />
                <h2 class="mt-3 text-lg font-bold">Collection and return</h2>
                <p class="mt-3 text-[0.95rem] leading-relaxed">We’ll confirm collection and return details when we call about your repair.</p>
            </x-ui.card>
        </div>
    </x-ui.section>

    <x-ui.section tone="white">
        <div class="grid gap-12 lg:grid-cols-[minmax(0,1fr)_24rem] lg:items-start">
            <div class="min-w-0">
                <x-content.prose :content="$content" />

                @if (filled($area->landmarks))
                    <section class="mt-10" aria-labelledby="local-areas">
                        <h2 id="local-areas" class="text-2xl font-extrabold">Around {{ $area->town }}</h2>
                        <div class="mt-4 space-y-4 leading-relaxed">
                            @foreach (preg_split('/\R{2,}/', trim($area->landmarks)) as $paragraph)
                                <p>{{ $paragraph }}</p>
                            @endforeach
                        </div>
                    </section>
                @endif
            </div>

            <aside class="space-y-6 lg:sticky lg:top-28">
                <x-ui.card padding="p-6">
                    <h2 class="text-xl font-bold">Check your postcode</h2>
                    <div class="mt-4">
                        <livewire:postcode-checker variant="compact" id-prefix="location-postcode" />
                    </div>
                </x-ui.card>
                <x-site.area-map :areas="collect([$area])" :title="'Map of '.$area->town" />
            </aside>
        </div>
    </x-ui.section>

    @if ($services->isNotEmpty())
        <x-ui.section tone="ice">
            <x-ui.section-heading eyebrow="Repairs in {{ $area->town }}" title="What we can fix">
                Tell us which repair you need and we’ll discuss the full details with you.
            </x-ui.section-heading>
            <ul class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($services->take(8) as ['service' => $service])
                    <li><x-site.service-card :name="$service->name" :url="$service->url()" :icon="$service->icon" /></li>
                @endforeach
            </ul>
        </x-ui.section>
    @endif

    @if ($reviews->isNotEmpty())
        <x-ui.section tone="white">
            <x-ui.section-heading eyebrow="Reviews" title="What customers say" />
            <ul class="mt-12 grid gap-6 md:grid-cols-3">
                @foreach ($reviews as $review)
                    <li><x-site.review-card :review="$review" /></li>
                @endforeach
            </ul>
        </x-ui.section>
    @endif

    @if ($faqs->isNotEmpty())
        <x-ui.section tone="white" width="narrow">
            <x-ui.section-heading eyebrow="FAQs" title="Questions from {{ $area->town }} customers" />
            <x-site.faq-list :faqs="$faqs" class="mt-10" />
        </x-ui.section>
    @endif

    @if ($otherAreas->isNotEmpty())
        <x-ui.section tone="ice" spacing="tight">
            <h2 class="text-center text-2xl font-extrabold">We also collect from</h2>
            <ul class="mt-8 flex flex-wrap justify-center gap-2.5">
                @foreach ($otherAreas as $other)
                    <li><a href="{{ $other->url() }}" class="inline-flex rounded-full border border-ice-100 bg-white px-4 py-2 font-semibold text-navy-900 hover:border-sky-200">{{ $other->town }}</a></li>
                @endforeach
            </ul>
        </x-ui.section>
    @endif

    <x-site.contact-actions :title="'Need your iPhone fixed in '.$area->town.'?'" />
</x-layouts.app>
