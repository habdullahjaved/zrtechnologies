{{--
    A vertical (9:16) video card. Placeholders show a branded "coming soon"
    card. YouTube videos use a facade: only the thumbnail loads until the
    visitor presses play. Uploaded files never autoplay with sound.
--}}
@props([
    'video',
    'headingLevel' => 3,
    'link' => true,
])

@php
    $poster = $video->posterUrl();
    $tag = 'h'.$headingLevel;
@endphp

<article {{ $attributes->class('group flex h-full flex-col') }}>
    <div class="relative aspect-9/16 overflow-hidden rounded-card bg-navy-900 shadow-soft ring-1 ring-navy-900/10">
        @if ($video->is_placeholder)
            <div class="absolute inset-0 bg-brand-gradient"></div>
            <x-ui.swoosh class="absolute inset-x-0 bottom-0 h-40 w-full text-white/20" />
            <div class="relative flex h-full flex-col items-center justify-center gap-4 p-6 text-center text-white">
                <span class="flex size-16 items-center justify-center rounded-full bg-white/15 ring-1 ring-white/30">
                    <x-heroicon-s-play class="size-7" aria-hidden="true" />
                </span>
                <p class="text-sm font-semibold tracking-wider text-sky-200 uppercase">Video coming soon</p>
                <p class="font-heading text-lg font-bold">{{ $video->placeholder_label ?: $video->title }}</p>
            </div>
        @elseif ($video->source === \App\Enums\VideoSource::Upload && $video->fileUrl())
            <video controls playsinline preload="none" @if ($poster) poster="{{ $poster }}" @endif class="size-full object-cover">
                <source src="{{ $video->fileUrl() }}">
                <p class="p-4 text-white">Your browser cannot play this video.</p>
            </video>
        @elseif ($video->embedUrl())
            <div x-data="embedFacade" class="size-full">
                <button type="button" x-show="! loaded" @click="load()" class="group/play relative size-full" aria-label="Play video: {{ $video->title }}">
                    @if ($poster)
                        <img src="{{ $poster }}" alt="" loading="lazy" decoding="async" class="absolute inset-0 size-full object-cover">
                    @endif
                    <span class="absolute inset-0 bg-navy-950/30"></span>
                    <span class="absolute inset-0 flex items-center justify-center">
                        <span class="flex size-16 items-center justify-center rounded-full bg-white text-royal-600 shadow-lift transition group-hover/play:scale-105">
                            <x-heroicon-s-play class="ml-1 size-7" aria-hidden="true" />
                        </span>
                    </span>
                </button>
                <template x-if="loaded">
                    <iframe src="{{ $video->embedUrl() }}" title="{{ $video->title }}" class="size-full" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen loading="lazy"></iframe>
                </template>
            </div>
        @else
            <a href="{{ $video->external_url }}" target="_blank" rel="noopener" class="relative block size-full" aria-label="Watch {{ $video->title }} on {{ $video->source->getLabel() }}">
                @if ($poster)
                    <img src="{{ $poster }}" alt="" loading="lazy" decoding="async" class="absolute inset-0 size-full object-cover">
                @else
                    <span class="absolute inset-0 bg-brand-gradient"></span>
                @endif
                <span class="absolute inset-0 flex flex-col items-center justify-center gap-3 bg-navy-950/30 text-white">
                    <x-heroicon-s-play-circle class="size-14" aria-hidden="true" />
                    <span class="text-sm font-semibold">Watch on {{ $video->source->getLabel() }}</span>
                </span>
            </a>
        @endif
    </div>

    <{{ $tag }} class="mt-4 text-lg font-bold">
        @if ($link && $video->url())
            <a href="{{ $video->url() }}" class="hover:text-royal-600">{{ $video->title }}</a>
        @else
            {{ $video->title }}
        @endif
    </{{ $tag }}>
    @if ($video->description && ! $video->is_placeholder)
        <p class="mt-1 line-clamp-2 text-sm leading-relaxed">{{ $video->description }}</p>
    @endif
</article>
