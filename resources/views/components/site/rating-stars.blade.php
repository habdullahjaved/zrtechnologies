@props([
    'rating',
    'size' => 'size-5',
])

<span {{ $attributes->class('inline-flex items-center gap-0.5') }} role="img" aria-label="Rated {{ $rating }} out of 5">
    @for ($star = 1; $star <= 5; $star++)
        <x-heroicon-s-star @class([$size, 'text-warning-500' => $star <= round($rating), 'text-slate-300' => $star > round($rating)]) aria-hidden="true" />
    @endfor
</span>
