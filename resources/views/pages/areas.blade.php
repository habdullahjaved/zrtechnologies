<x-layouts.app :seo="$seo">
    <x-site.page-header :title="$page->title" :eyebrow="$page->eyebrow" :breadcrumbs="$breadcrumbs">
        {{ $page->intro }}
    </x-site.page-header>

    <x-ui.section tone="ice" spacing="tight">
        <div class="mx-auto max-w-3xl">
            <x-ui.card padding="p-5 sm:p-8">
                <livewire:postcode-checker id-prefix="areas-postcode" />
            </x-ui.card>
        </div>
    </x-ui.section>

    <x-ui.section tone="white">
        <x-ui.section-heading eyebrow="Where we collect" title="Towns we cover">
            Enter your postcode above for an instant answer, or find your town below.
        </x-ui.section-heading>

        @if ($areas->isEmpty())
            <p class="mt-10 text-center">Our collection areas are being updated. Please call or message us and we will help.</p>
        @else
            <ul class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($areas as $area)
                    <li>
                        <x-ui.card class="h-full">
                            <div class="flex items-start justify-between gap-3">
                                <h3 class="text-xl font-bold"><a href="{{ $area->url() }}" class="hover:text-royal-600">{{ $area->name }}</a></h3>
                                <x-heroicon-o-map-pin class="size-6 shrink-0 text-royal-600" aria-hidden="true" />
                            </div>
                            @if ($area->summary)
                                <p class="mt-2 leading-relaxed">{{ $area->summary }}</p>
                            @endif
                            <dl class="mt-4 grid gap-2 text-sm">
                                <div>
                                    <dt class="font-semibold text-navy-900">Postcodes</dt>
                                    <dd class="mt-1 flex flex-wrap gap-1.5">
                                        @foreach ($area->postcode_districts as $district)
                                            <x-ui.badge>{{ $district }}</x-ui.badge>
                                        @endforeach
                                    </dd>
                                </div>
                                <div class="flex gap-1.5">
                                    <dt class="font-semibold text-navy-900">Collection &amp; return:</dt>
                                    <dd>{{ $area->collectionFeeLabel() }}</dd>
                                </div>
                                @if ($area->typical_collection_times)
                                    <div>
                                        <dt class="font-semibold text-navy-900">When we collect</dt>
                                        <dd class="mt-0.5">{{ $area->typical_collection_times }}</dd>
                                    </div>
                                @endif
                            </dl>
                            <a href="{{ $area->url() }}" class="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-royal-600 hover:underline">iPhone repair in {{ $area->town }} <x-heroicon-m-arrow-right class="size-4" aria-hidden="true" /></a>
                        </x-ui.card>
                    </li>
                @endforeach
            </ul>
        @endif

        @if ($areas->isNotEmpty())
            <x-site.area-map :areas="$areas" class="mt-12" />
        @endif
    </x-ui.section>

    <x-site.contact-actions />
</x-layouts.app>
