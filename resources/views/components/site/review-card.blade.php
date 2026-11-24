@props([
    'review',
])

<figure {{ $attributes->class('flex h-full flex-col rounded-card border border-ice-100 bg-white p-6 shadow-soft') }}>
    <div class="flex items-center justify-between gap-3">
        <x-site.rating-stars :rating="$review->rating" />
        @if ($review->is_sample)
            <x-ui.badge tone="warning">Sample review (development only)</x-ui.badge>
        @endif
    </div>
    <blockquote class="mt-4 flex-1 leading-relaxed text-navy-900">
        <p>“{{ $review->body }}”</p>
    </blockquote>
    <figcaption class="mt-5 flex items-center justify-between gap-3 text-sm">
        <span>
            <span class="font-semibold text-navy-900">{{ $review->displayName() }}</span>
            @if ($review->serviceArea)
                <span class="block">{{ $review->serviceArea->town }}</span>
            @endif
        </span>
        <span class="text-right">
            <span class="block">{{ $review->source->getLabel() }}</span>
            <time datetime="{{ $review->reviewed_at->toDateString() }}">{{ $review->reviewed_at->format('F Y') }}</time>
        </span>
    </figcaption>
</figure>
