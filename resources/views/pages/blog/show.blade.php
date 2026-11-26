<x-layouts.app :seo="$seo">
    <x-slot:head>
        <link rel="alternate" type="application/rss+xml" title="{{ config('app.name') }} blog" href="{{ route('blog.feed') }}">
    </x-slot:head>

    <x-site.page-header :title="$post->title" :eyebrow="$post->category->name" :breadcrumbs="$breadcrumbs">
        {{ $post->excerpt }}
        <p class="mt-5 flex flex-wrap items-center gap-x-3 gap-y-1 text-base text-sky-100">
            @if ($post->author)
                <span>By <a href="{{ $post->author->url() }}" class="font-semibold text-white hover:underline">{{ $post->author->name }}</a></span>
                <span aria-hidden="true">·</span>
            @endif
            <span>Published <time datetime="{{ $post->published_at?->toDateString() }}">{{ $post->published_at?->format('j F Y') }}</time></span>
            @if ($post->content_updated_at && $post->content_updated_at->isAfter($post->published_at))
                <span aria-hidden="true">·</span>
                <span>Last updated <time datetime="{{ $post->content_updated_at->toDateString() }}">{{ $post->content_updated_at->format('j F Y') }}</time></span>
            @endif
            <span aria-hidden="true">·</span>
            <span>{{ $post->reading_minutes }} min read</span>
        </p>
    </x-site.page-header>

    <x-ui.section tone="white">
        <div class="grid gap-12 lg:grid-cols-[minmax(0,1fr)_20rem] lg:items-start">
            <article class="mx-auto w-full max-w-3xl min-w-0">
                @if ($post->featuredImageUrl())
                    <img src="{{ $post->featuredImageUrl() }}" alt="{{ $post->featured_image_alt ?? '' }}" width="1200" height="630" fetchpriority="high" decoding="async" class="mb-10 w-full rounded-card border border-ice-100 object-cover">
                @endif

                @if (count($content->tableOfContents()) >= 2)
                    <x-site.toc :headings="$content->tableOfContents()" class="mb-10 lg:hidden" />
                @endif

                <x-content.prose :content="$content" />

                @if ($faqs->isNotEmpty())
                    <section class="mt-14" aria-labelledby="post-faqs">
                        <h2 id="post-faqs" class="mb-6 text-2xl font-extrabold sm:text-3xl">Frequently asked questions</h2>
                        <x-site.faq-list :faqs="$faqs" />
                    </section>
                @endif

                @if ($post->service)
                    <aside class="relative mt-14 overflow-hidden rounded-card bg-brand-gradient p-6 text-white sm:p-8">
                        <x-ui.swoosh class="absolute -right-10 -bottom-10 h-40 w-72 text-white/20" />
                        <div class="relative">
                            <p class="font-heading text-xl font-extrabold sm:text-2xl">Need iPhone {{ \Illuminate\Support\Str::lower($post->service->name) }}?</p>
                            <p class="mt-2 max-w-xl leading-relaxed text-sky-100">{{ $post->service->summary }}</p>
                            <x-ui.button :href="$post->service->url()" variant="light" size="lg" class="mt-5">
                                Book a Repair
                                <x-heroicon-m-arrow-right class="size-5" aria-hidden="true" />
                            </x-ui.button>
                        </div>
                    </aside>
                @endif

                @if ($post->tags->isNotEmpty())
                    <ul class="mt-10 flex flex-wrap gap-2" aria-label="Tags">
                        @foreach ($post->tags as $tag)
                            <li><a href="{{ $tag->url() }}" class="inline-flex rounded-full bg-ice-50 px-3 py-1 text-sm font-semibold text-royal-700 ring-1 ring-ice-100 hover:ring-sky-200">#{{ $tag->name }}</a></li>
                        @endforeach
                    </ul>
                @endif

                <x-site.share-links :url="$shareUrl" :title="$post->title" class="mt-8 border-t border-ice-100 pt-6" />

                @if ($post->author)
                    <section class="mt-10 flex gap-5 rounded-card border border-ice-100 bg-ice-50 p-6" aria-label="About the author">
                        @if ($post->author->photoUrl())
                            <img src="{{ $post->author->photoUrl() }}" alt="" width="80" height="80" loading="lazy" class="size-20 shrink-0 rounded-full object-cover">
                        @else
                            <img src="{{ asset('brand/zr-mark-colour-160w.webp') }}" alt="" width="80" height="80" loading="lazy" class="size-20 shrink-0 rounded-full bg-white p-3">
                        @endif
                        <div>
                            <p class="font-heading text-lg font-bold text-navy-900"><a href="{{ $post->author->url() }}" class="hover:text-royal-600">{{ $post->author->name }}</a></p>
                            @if ($post->author->role_title)
                                <p class="text-sm font-semibold text-royal-600">{{ $post->author->role_title }}</p>
                            @endif
                            @if ($post->author->bio)
                                <p class="mt-2 leading-relaxed">{{ \Illuminate\Support\Str::limit($post->author->bio, 260) }}</p>
                            @endif
                        </div>
                    </section>
                @endif
            </article>

            <aside class="hidden space-y-6 lg:sticky lg:top-28 lg:block">
                <x-site.toc :headings="$content->tableOfContents()" />
                <x-ui.card padding="p-6">
                    <p class="font-heading text-lg font-bold text-navy-900">Need a repair?</p>
                    <p class="mt-1.5 text-[0.95rem] leading-relaxed">We collect your iPhone, repair it and bring it back, with a warranty.</p>
                    <x-ui.button :href="route('booking.create')" class="mt-4 w-full">Book a Repair</x-ui.button>
                </x-ui.card>
            </aside>
        </div>
    </x-ui.section>

    @if ($related->isNotEmpty())
        <x-ui.section tone="ice">
            <x-ui.section-heading eyebrow="Keep reading" title="Related guides" />
            <ul class="mt-12 grid gap-6 md:grid-cols-3">
                @foreach ($related as $relatedPost)
                    <li><x-site.post-card :post="$relatedPost" /></li>
                @endforeach
            </ul>
        </x-ui.section>
    @endif

    <x-site.contact-actions />
</x-layouts.app>
