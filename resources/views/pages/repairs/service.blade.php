<x-layouts.app :seo="$seo">
    <x-site.page-header :title="$service->heading()" eyebrow="iPhone {{ $service->name }}" :breadcrumbs="$breadcrumbs">
        {{ $service->intro ?? $service->summary }}
        <div class="mt-7">
            <x-ui.button :href="$bookingUrl" size="lg">
                <x-heroicon-s-chat-bubble-left-right class="size-5" aria-hidden="true" />
                Book this repair
            </x-ui.button>
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
                    <h2 class="text-xl font-bold">Why choose us</h2>
                    <ul class="mt-4 space-y-3 text-[0.95rem]">
                        @foreach ([
                            ['icon' => 'heroicon-s-truck', 'text' => 'We collect from your home or work and bring it back'],
                            ['icon' => 'heroicon-s-shield-check', 'text' => 'Warranty on the part and our work'],
                            ['icon' => 'heroicon-s-chat-bubble-left-right', 'text' => 'We explain the repair and cost before you decide'],
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
