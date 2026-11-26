{{-- A repair card linking to its service page. --}}
@props([
    'name',
    'url',
    'summary' => null,
    'icon' => null,
    'headingLevel' => 3,
])

@php($tag = 'h'.$headingLevel)

<x-ui.card {{ $attributes->class('group relative flex h-full flex-col') }} :interactive="true">
    @if ($icon)
        <img src="{{ asset("images/icons/icon-{$icon}-navy-96.webp") }}" alt="" width="56" height="56" loading="lazy" class="size-14 rounded-2xl bg-ice-50 p-2">
    @endif
    <{{ $tag }} class="mt-5 text-xl font-bold">
        <a href="{{ $url }}" class="after:absolute after:inset-0 after:rounded-card hover:text-royal-600">{{ $name }}</a>
    </{{ $tag }}>
    @if ($summary)
        <p class="mt-2 flex-1 leading-relaxed">{{ $summary }}</p>
    @endif
    <p class="mt-5 flex items-center justify-end gap-3">
        <x-heroicon-m-arrow-right class="size-5 text-royal-600 transition motion-safe:group-hover:translate-x-1" aria-hidden="true" />
    </p>
</x-ui.card>
