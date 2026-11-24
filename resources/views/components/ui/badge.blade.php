@props([
    'tone' => 'brand',
])

@php
    $tones = [
        'brand' => 'bg-ice-50 text-royal-700 ring-ice-100',
        'success' => 'bg-green-50 text-success-700 ring-green-200',
        'warning' => 'bg-amber-50 text-warning-700 ring-amber-200',
        'danger' => 'bg-red-50 text-red-700 ring-red-200',
        'neutral' => 'bg-slate-100 text-slate-700 ring-slate-200',
        'on-dark' => 'bg-white/10 text-white ring-white/25',
    ];
@endphp

<span {{ $attributes->class(['inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset', $tones[$tone] ?? $tones['brand']]) }}>
    {{ $slot }}
</span>
