@props([
    'interactive' => false,
    'padding' => 'p-6 sm:p-8',
])

<div {{ $attributes->class([
    'rounded-card border border-ice-100 bg-white shadow-soft',
    $padding,
    'transition duration-300 motion-safe:hover:-translate-y-1 hover:border-sky-200 hover:shadow-lift' => $interactive,
]) }}>
    {{ $slot }}
</div>
