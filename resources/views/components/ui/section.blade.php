@props([
    'tone' => 'white',
    'spacing' => 'default',
    'width' => 'default',
])

@php
    $tones = [
        'white' => 'bg-white',
        'ice' => 'bg-ice-50',
        'navy' => 'bg-navy-900 text-sky-200 [&_h2]:text-white [&_h3]:text-white',
        'gradient' => 'bg-brand-gradient text-white [&_h2]:text-white [&_h3]:text-white',
    ];

    $spacings = [
        'default' => 'py-16 sm:py-20 lg:py-24',
        'tight' => 'py-10 sm:py-12',
        'none' => '',
    ];

    $widths = [
        'default' => 'max-w-7xl',
        'narrow' => 'max-w-3xl',
        'wide' => 'max-w-screen-2xl',
    ];
@endphp

<section {{ $attributes->class(['relative overflow-hidden', $tones[$tone] ?? $tones['white'], $spacings[$spacing] ?? $spacings['default']]) }}>
    <div @class(['relative mx-auto px-4 sm:px-6 lg:px-8', $widths[$width] ?? $widths['default']])>
        {{ $slot }}
    </div>
</section>
