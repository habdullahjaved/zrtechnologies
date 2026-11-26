<x-layouts.app :seo="$seo">
    <x-site.page-header :title="$repair->name.' for '.$model->name" :eyebrow="$model->name" :breadcrumbs="$breadcrumbs">
        {{ $page->intro ?? $repair->summary }}
        <div class="mt-7">
            <x-ui.button :href="$bookingUrl" size="lg">
                <x-heroicon-s-chat-bubble-left-right class="size-5" aria-hidden="true" />
                Book this repair
            </x-ui.button>
        </div>
    </x-site.page-header>

    <x-ui.section tone="white">
        <div class="grid gap-12 lg:grid-cols-[minmax(0,1fr)_22rem] lg:items-start">
            <x-content.prose :content="$content" />
            <aside class="space-y-6 lg:sticky lg:top-28">
                <x-ui.card padding="p-6">
                    <h2 class="text-xl font-bold">Ask about this repair</h2>
                    <p class="mt-3 text-[0.95rem] leading-relaxed">Choose this repair and continue to select a collection time that works for you.</p>
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
