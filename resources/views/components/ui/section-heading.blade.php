@props([
    'eyebrow' => null,
    'title',
    'align' => 'center',
    'level' => 'h2',
])

<div {{ $attributes->class(['max-w-3xl', 'mx-auto text-center' => $align === 'center']) }}>
    @if ($eyebrow)
        <p class="mb-3 text-sm font-semibold tracking-wider text-royal-600 uppercase">{{ $eyebrow }}</p>
    @endif

    <{{ $level }} class="text-3xl font-extrabold sm:text-4xl">{{ $title }}</{{ $level }}>

    @if ($slot->isNotEmpty())
        <div class="mt-4 text-lg leading-relaxed">{{ $slot }}</div>
    @endif
</div>
