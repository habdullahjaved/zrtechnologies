<x-layouts.app :seo="$seo">
    <x-site.page-header :title="$repair->name.' for '.$model->name" :eyebrow="$model->name" :breadcrumbs="$breadcrumbs">
        {{ $page->intro ?? $repair->summary }}
        <div class="mt-7 flex flex-col gap-3 sm:flex-row sm:items-center">
            <x-ui.button :href="$bookingUrl" size="lg">
                <x-heroicon-s-wrench-screwdriver class="size-5" aria-hidden="true" />
                Book this repair
            </x-ui.button>
            <p class="text-white">
                <span class="text-sm text-sky-200">From</span>
                <span class="font-heading text-3xl font-extrabold">{{ \App\Support\Money::format($fromPence) }}</span>
            </p>
        </div>
    </x-site.page-header>

    <x-ui.section tone="white">
        <div class="grid gap-12 lg:grid-cols-[minmax(0,1fr)_22rem] lg:items-start">
            <x-content.prose :content="$content" />

            <aside class="space-y-6 lg:sticky lg:top-28">
                <x-ui.card padding="p-6">
                    <h2 class="text-xl font-bold">Price</h2>
                    <ul class="mt-4 space-y-3">
                        @foreach ($prices as $price)
                            <li class="flex items-baseline justify-between gap-4">
                                <span>{{ $price->partQuality?->name ?? $repair->name }}@if ($price->warrantyLabel())<span class="block text-sm">{{ $price->warrantyLabel() }}</span>@endif</span>
                                <strong class="font-heading text-xl text-navy-900">{{ $price->formattedPrice() }}</strong>
                            </li>
                        @endforeach
                    </ul>
                    <x-ui.button :href="$bookingUrl" class="mt-6 w-full">Book this repair</x-ui.button>
                </x-ui.card>
                <p class="text-[0.95rem]">
                    <a href="{{ $model->url() }}" class="font-semibold text-royal-600 hover:underline">All {{ $model->name }} repairs</a>
                    @if ($service)
                        · <a href="{{ $service->url() }}" class="font-semibold text-royal-600 hover:underline">About {{ \Illuminate\Support\Str::lower($service->name) }}</a>
                    @endif
                </p>
            </aside>
        </div>
    </x-ui.section>

    <x-site.contact-actions />
</x-layouts.app>
