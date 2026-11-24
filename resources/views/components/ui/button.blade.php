@props([
    'href' => null,
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
    'external' => false,
])

@php
    $base = 'inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-xl font-semibold transition duration-200 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-400 disabled:pointer-events-none disabled:opacity-60 motion-safe:hover:-translate-y-0.5';

    $variants = [
        'primary' => 'bg-royal-600 text-white shadow-soft hover:bg-blue-500 hover:shadow-lift',
        'secondary' => 'border border-ice-100 bg-white text-navy-900 shadow-sm hover:border-sky-200 hover:bg-ice-50',
        'ghost' => 'text-royal-600 hover:bg-ice-50',
        'whatsapp' => 'bg-whatsapp text-white shadow-soft hover:brightness-110',
        'light' => 'bg-white text-navy-900 shadow-soft hover:bg-ice-50',
        'outline-light' => 'border border-white/40 text-white hover:border-white hover:bg-white/10',
    ];

    $sizes = [
        'sm' => 'px-3.5 py-2 text-sm',
        'md' => 'px-5 py-2.5 text-[0.95rem]',
        'lg' => 'px-6 py-3.5 text-base',
    ];

    $classes = $base.' '.($variants[$variant] ?? $variants['primary']).' '.($sizes[$size] ?? $sizes['md']);
@endphp

@if ($href)
    <a
        href="{{ $href }}"
        @if ($external) target="_blank" rel="noopener" @endif
        {{ $attributes->class($classes) }}
    >{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->class($classes) }}>{{ $slot }}</button>
@endif
