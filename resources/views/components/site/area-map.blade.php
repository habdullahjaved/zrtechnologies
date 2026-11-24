{{--
    Coverage map (OpenStreetMap, no API key, no cookies). The map only loads
    when the visitor asks for it, so it never slows the page down.
--}}
@props([
    'areas',
    'title' => 'Map of the areas we cover',
])

@php
    $points = $areas->filter(fn ($area) => $area->centre_lat !== null && $area->centre_lng !== null);
    $map = null;

    if ($points->isNotEmpty()) {
        $lats = $points->map(fn ($area) => (float) $area->centre_lat);
        $lngs = $points->map(fn ($area) => (float) $area->centre_lng);
        $padding = $points->count() === 1 ? 0.06 : 0.04;
        $bbox = implode(',', [$lngs->min() - $padding * 2, $lats->min() - $padding, $lngs->max() + $padding * 2, $lats->max() + $padding]);
        $centre = $points->count() === 1 ? $points->first() : null;
        $marker = $centre ? '&marker='.$centre->centre_lat.','.$centre->centre_lng : '';
        $map = [
            'embed' => 'https://www.openstreetmap.org/export/embed.html?bbox='.rawurlencode($bbox).'&layer=mapnik'.$marker,
            'link' => 'https://www.openstreetmap.org/?mlat='.$lats->avg().'&mlon='.$lngs->avg().'#map=11/'.round($lats->avg(), 4).'/'.round($lngs->avg(), 4),
        ];
    }
@endphp

@if ($map)
    <div {{ $attributes->class('overflow-hidden rounded-card border border-ice-100 bg-ice-50 shadow-soft') }} x-data="embedFacade">
        <div class="relative aspect-4/3 sm:aspect-16/9">
            <button type="button" x-show="! loaded" @click="load()" class="absolute inset-0 flex flex-col items-center justify-center gap-3 bg-[radial-gradient(circle_at_center,var(--color-ice-100),var(--color-ice-50))] p-6 text-center">
                <span class="flex size-14 items-center justify-center rounded-full bg-white text-royal-600 shadow-soft">
                    <x-heroicon-o-map class="size-7" aria-hidden="true" />
                </span>
                <span class="font-heading text-lg font-bold text-navy-900">Show the map</span>
                <span class="text-sm">{{ $points->pluck('town')->unique()->implode(' · ') }}</span>
            </button>
            <template x-if="loaded">
                <iframe src="{{ $map['embed'] }}" title="{{ $title }}" class="absolute inset-0 size-full" loading="lazy" referrerpolicy="no-referrer"></iframe>
            </template>
        </div>
        <p class="border-t border-ice-100 bg-white px-4 py-2.5 text-xs">
            Map data © <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener" class="underline">OpenStreetMap contributors</a>.
            <a href="{{ $map['link'] }}" target="_blank" rel="noopener" class="font-semibold text-royal-600 underline">Open a larger map</a>
        </p>
    </div>
@endif
