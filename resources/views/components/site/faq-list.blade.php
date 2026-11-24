{{--
    FAQ accordion using native <details>, so answers stay in the HTML for
    search engines and work without JavaScript. Pass the same FAQs to the
    page's FAQPage structured data.
--}}
@props([
    'faqs',
    'headingLevel' => 3,
])

<div {{ $attributes->class('divide-y divide-ice-100 overflow-hidden rounded-card border border-ice-100 bg-white shadow-soft') }}>
    @foreach ($faqs as $faq)
        <details class="group" @if ($loop->first && $faqs->count() <= 3) open @endif>
            <summary class="flex cursor-pointer list-none items-start justify-between gap-4 px-5 py-4 hover:bg-ice-50 sm:px-6 [&::-webkit-details-marker]:hidden">
                <span role="heading" aria-level="{{ $headingLevel }}" class="font-heading text-base font-bold text-navy-900 sm:text-lg">{{ $faq->question }}</span>
                <x-heroicon-m-plus class="mt-1 size-5 shrink-0 text-royal-600 transition group-open:rotate-45" aria-hidden="true" />
            </summary>
            <div class="space-y-3 px-5 pb-5 leading-relaxed sm:px-6">
                @foreach ($faq->answerParagraphs() as $paragraph)
                    <p>{{ $paragraph }}</p>
                @endforeach
            </div>
        </details>
    @endforeach
</div>
