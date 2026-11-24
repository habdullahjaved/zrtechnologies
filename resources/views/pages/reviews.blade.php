<x-layouts.app :seo="$seo">
    <x-site.page-header :title="$page->title" :eyebrow="$page->eyebrow" :breadcrumbs="$breadcrumbs">
        {{ $page->intro }}
        @if ($reviewUrl)
            <div class="mt-7">
                <x-ui.button :href="$reviewUrl" variant="light" size="lg" :external="true">
                    <x-heroicon-s-star class="size-5 text-warning-500" aria-hidden="true" />
                    Leave us a review on Google
                </x-ui.button>
            </div>
        @endif
    </x-site.page-header>

    <x-ui.section tone="ice">
        @if ($reviews->isEmpty())
            <div class="mx-auto max-w-2xl text-center">
                <x-heroicon-o-chat-bubble-left-right class="mx-auto size-12 text-royal-600" aria-hidden="true" />
                <h2 class="mt-4 text-2xl font-extrabold">Our first reviews are on their way</h2>
                <p class="mt-3 leading-relaxed">We only ever show genuine reviews from real customers. If we have repaired your iPhone, we would really appreciate you sharing your experience.</p>
                <div class="mt-6 flex flex-col justify-center gap-3 sm:flex-row">
                    @if ($reviewUrl)
                        <x-ui.button :href="$reviewUrl" :external="true">Leave a review</x-ui.button>
                    @endif
                    <x-ui.button :href="route('booking.create')" variant="secondary">Book a repair</x-ui.button>
                </div>
            </div>
        @else
            @if ($averageRating !== null)
                <div class="mb-10 flex flex-col items-center gap-2 text-center">
                    <x-site.rating-stars :rating="$averageRating" size="size-7" />
                    <p class="font-heading text-lg font-bold text-navy-900">{{ number_format($averageRating, 1) }} out of 5 from {{ $reviews->total() }} {{ \Illuminate\Support\Str::plural('review', $reviews->total()) }}</p>
                    @if ($googleProfileUrl)
                        <a href="{{ $googleProfileUrl }}" target="_blank" rel="noopener" class="text-sm font-semibold text-royal-600 hover:underline">See all our reviews on Google</a>
                    @endif
                </div>
            @endif

            <ul class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($reviews as $review)
                    <li><x-site.review-card :review="$review" /></li>
                @endforeach
            </ul>

            <div class="mt-10">{{ $reviews->links() }}</div>
        @endif
    </x-ui.section>

    <x-site.contact-actions />
</x-layouts.app>
