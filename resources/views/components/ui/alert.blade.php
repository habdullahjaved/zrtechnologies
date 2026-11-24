@props([
    'tone' => 'info',
    'title' => null,
])

@php
    $tones = [
        'info' => ['box' => 'border-sky-200 bg-ice-50 text-navy-900', 'icon' => 'heroicon-s-information-circle', 'iconClass' => 'text-royal-600'],
        'success' => ['box' => 'border-green-200 bg-green-50 text-green-900', 'icon' => 'heroicon-s-check-circle', 'iconClass' => 'text-success-700'],
        'warning' => ['box' => 'border-amber-200 bg-amber-50 text-amber-950', 'icon' => 'heroicon-s-exclamation-triangle', 'iconClass' => 'text-warning-700'],
        'danger' => ['box' => 'border-red-200 bg-red-50 text-red-900', 'icon' => 'heroicon-s-x-circle', 'iconClass' => 'text-danger-600'],
    ];
    $style = $tones[$tone] ?? $tones['info'];
@endphp

<div {{ $attributes->class(['flex gap-3 rounded-xl border p-4', $style['box']]) }} role="{{ in_array($tone, ['warning', 'danger'], true) ? 'alert' : 'status' }}">
    <x-dynamic-component :component="$style['icon']" @class(['mt-0.5 size-5 shrink-0', $style['iconClass']]) aria-hidden="true" />
    <div class="text-sm leading-relaxed">
        @if ($title)
            <p class="font-semibold">{{ $title }}</p>
        @endif
        {{ $slot }}
    </div>
</div>
