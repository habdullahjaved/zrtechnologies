<x-layouts.app :seo="$seo">
    <x-site.page-header :title="$video->title" eyebrow="Video" :breadcrumbs="$breadcrumbs">
        {{ $video->description }}
    </x-site.page-header>

    <x-ui.section tone="white">
        <div class="grid gap-12 lg:grid-cols-[22rem_minmax(0,1fr)] lg:items-start">
            <x-site.video-card :video="$video" :link="false" :heading-level="2" class="mx-auto w-full max-w-xs [&>h2]:sr-only" />

            <div class="min-w-0">
                @if ($video->service)
                    <x-ui.card padding="p-6">
                        <h2 class="text-xl font-bold">iPhone {{ $video->service->name }}</h2>
                        @if ($video->service->summary)
                            <p class="mt-2 leading-relaxed">{{ $video->service->summary }}</p>
                        @endif
                        <x-ui.button :href="$video->service->url()" variant="secondary" class="mt-4">
                            Book a Repair
                            <x-heroicon-m-arrow-right class="size-4" aria-hidden="true" />
                        </x-ui.button>
                    </x-ui.card>
                @endif

                @if (filled($video->transcript))
                    <section class="mt-10" aria-labelledby="transcript">
                        <h2 id="transcript" class="text-2xl font-extrabold">Transcript</h2>
                        <div class="mt-4 space-y-4 leading-relaxed">
                            @foreach (preg_split('/\R{2,}/', trim($video->transcript)) as $paragraph)
                                <p>{{ $paragraph }}</p>
                            @endforeach
                        </div>
                    </section>
                @endif
            </div>
        </div>
    </x-ui.section>

    @if ($moreVideos->isNotEmpty())
        <x-ui.section tone="ice">
            <x-ui.section-heading eyebrow="More videos" title="Keep watching" />
            <ul class="mt-10 grid grid-cols-2 gap-5 md:grid-cols-4">
                @foreach ($moreVideos as $other)
                    <li><x-site.video-card :video="$other" /></li>
                @endforeach
            </ul>
        </x-ui.section>
    @endif

    <x-site.contact-actions />
</x-layouts.app>
