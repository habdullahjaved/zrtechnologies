<x-layouts.app :seo="$seo">
    <x-site.page-header :title="$page->title" :eyebrow="$page->eyebrow" :breadcrumbs="$breadcrumbs">
        {{ $page->intro }}
        <div class="mt-7 flex flex-col gap-3 sm:flex-row">
            <x-ui.button :href="route('booking.create')" size="lg">
                <x-heroicon-s-wrench-screwdriver class="size-5" aria-hidden="true" />
                Book a Repair
            </x-ui.button>
            <x-ui.button :href="route('prices')" variant="outline-light" size="lg">See all prices</x-ui.button>
        </div>
    </x-site.page-header>

    @if ($services->isNotEmpty())
        <x-ui.section tone="ice">
            <x-ui.section-heading eyebrow="What we repair" title="Choose your repair">
                Every repair includes collection and return, quality-tested parts and a warranty.
            </x-ui.section-heading>

            <ul class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($services as ['service' => $service, 'from_pence' => $fromPence])
                    <li>
                        <x-site.service-card :name="$service->name" :url="$service->url()" :summary="$service->summary" :icon="$service->icon" :from-pence="$fromPence" />
                    </li>
                @endforeach
            </ul>
        </x-ui.section>
    @endif

    <x-ui.section tone="white">
        <div class="grid gap-12 lg:grid-cols-[minmax(0,1fr)_24rem] lg:items-start">
            <x-content.prose :content="$content" />

            <aside class="space-y-6 lg:sticky lg:top-28">
                <x-ui.card padding="p-6">
                    <h2 class="text-xl font-bold">Check we collect from you</h2>
                    <p class="mt-1.5 mb-4 text-sm">Enter your postcode for an instant answer.</p>
                    <livewire:postcode-checker variant="compact" id-prefix="pillar-postcode" />
                </x-ui.card>
            </aside>
        </div>
    </x-ui.section>

    @if ($modelFamilies->isNotEmpty())
        <x-ui.section tone="ice">
            <x-ui.section-heading eyebrow="Repairs by model" title="Find your iPhone">
                Every model from the iPhone 8 to the latest line-up, with prices for each repair.
            </x-ui.section-heading>

            @if ($popularModels->isNotEmpty())
                <ul class="mx-auto mt-10 flex max-w-4xl flex-wrap justify-center gap-2.5">
                    @foreach ($popularModels as $model)
                        <li><a href="{{ $model->url() }}" class="inline-flex rounded-full bg-royal-600 px-4 py-2 font-semibold text-white shadow-soft hover:bg-blue-500">{{ $model->name }}</a></li>
                    @endforeach
                </ul>
            @endif

            <div class="mt-12 grid gap-x-8 gap-y-8 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($modelFamilies as $family => $familyModels)
                    <div>
                        <h3 class="text-base font-bold">{{ $family }}</h3>
                        <ul class="mt-3 space-y-2">
                            @foreach ($familyModels as $model)
                                <li><a href="{{ $model->url() }}" class="text-royal-600 hover:underline">{{ $model->name }} repair</a></li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        </x-ui.section>
    @endif

    @if ($areas->isNotEmpty())
        <x-ui.section tone="white">
            <x-ui.section-heading eyebrow="Collection and return" title="Towns we collect from">
                We come to your home or workplace across East Lancashire.
            </x-ui.section-heading>
            <ul class="mt-10 flex flex-wrap justify-center gap-3">
                @foreach ($areas as $area)
                    <li><a href="{{ $area->url() }}" class="inline-flex items-center gap-2 rounded-full border border-ice-100 bg-ice-50 px-4 py-2 font-semibold text-navy-900 hover:border-sky-200"><x-heroicon-s-map-pin class="size-4 text-royal-600" aria-hidden="true" /> {{ $area->town }}</a></li>
                @endforeach
            </ul>
        </x-ui.section>
    @endif

    @if ($posts->isNotEmpty())
        <x-ui.section tone="ice">
            <x-ui.section-heading eyebrow="Guides" title="Advice from our repair team" />
            <ul class="mt-12 grid gap-6 md:grid-cols-3">
                @foreach ($posts as $post)
                    <li><x-site.post-card :post="$post" /></li>
                @endforeach
            </ul>
        </x-ui.section>
    @endif

    @if ($faqs->isNotEmpty())
        <x-ui.section tone="white" width="narrow">
            <x-ui.section-heading eyebrow="FAQs" title="iPhone repair questions" />
            <x-site.faq-list :faqs="$faqs" class="mt-10" />
        </x-ui.section>
    @endif

    <x-site.contact-actions />
</x-layouts.app>
