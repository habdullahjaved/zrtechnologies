<x-layouts.app :seo="$seo">
    <x-slot:head>
        <link rel="alternate" type="application/rss+xml" title="{{ config('app.name') }} blog" href="{{ route('blog.feed') }}">
    </x-slot:head>

    <x-site.page-header :title="$heading" :eyebrow="$page?->eyebrow ?? 'Blog'" :breadcrumbs="$breadcrumbs">
        {{ $intro }}
    </x-site.page-header>

    <x-ui.section tone="ice">
        @if ($categories->isNotEmpty())
            <nav aria-label="Blog categories" class="mb-10">
                <ul class="flex flex-wrap justify-center gap-2.5">
                    <li>
                        <a href="{{ route('blog.index') }}" @class(['inline-flex rounded-full px-4 py-2 text-sm font-semibold', 'bg-royal-600 text-white' => $activeCategory === null && $page !== null, 'border border-ice-100 bg-white text-navy-900 hover:border-sky-200' => ! ($activeCategory === null && $page !== null)])>All guides</a>
                    </li>
                    @foreach ($categories as $category)
                        <li>
                            <a href="{{ $category->url() }}" @class(['inline-flex rounded-full px-4 py-2 text-sm font-semibold', 'bg-royal-600 text-white' => $activeCategory?->is($category), 'border border-ice-100 bg-white text-navy-900 hover:border-sky-200' => ! $activeCategory?->is($category)]) @if ($activeCategory?->is($category)) aria-current="page" @endif>{{ $category->name }}</a>
                        </li>
                    @endforeach
                </ul>
            </nav>
        @endif

        @if ($posts->isEmpty())
            <div class="mx-auto max-w-2xl text-center">
                <x-heroicon-o-book-open class="mx-auto size-12 text-royal-600" aria-hidden="true" />
                <h2 class="mt-4 text-2xl font-extrabold">New guides are on the way</h2>
                <p class="mt-3 leading-relaxed">Our repair team is writing practical guides on battery health, screen protection, water damage and more. In the meantime, our FAQs answer the most common questions.</p>
                <x-ui.button :href="route('faqs')" variant="secondary" class="mt-6">Read the FAQs</x-ui.button>
            </div>
        @else
            <ul class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($posts as $post)
                    <li><x-site.post-card :post="$post" :heading-level="2" /></li>
                @endforeach
            </ul>

            <div class="mt-10">{{ $posts->links() }}</div>
        @endif
    </x-ui.section>

    <x-site.contact-actions />
</x-layouts.app>
