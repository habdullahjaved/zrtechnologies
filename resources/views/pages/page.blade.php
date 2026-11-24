<x-layouts.app :seo="$seo">
    <x-site.page-header :title="$page->title" :eyebrow="$page->eyebrow" :breadcrumbs="$breadcrumbs">
        {{ $page->intro }}
    </x-site.page-header>

    <x-ui.section tone="white">
        <div @class(['grid gap-10', 'lg:grid-cols-[16rem_minmax(0,1fr)] lg:items-start' => count($content->tableOfContents()) >= 3])>
            @if (count($content->tableOfContents()) >= 3)
                <x-site.toc :headings="$content->tableOfContents()" class="lg:sticky lg:top-28" />
            @endif

            <div class="mx-auto w-full max-w-3xl">
                @if ($isLegal && $page->updated_at)
                    <p class="mb-8 text-sm">Last updated <time datetime="{{ $page->updated_at->toDateString() }}">{{ $page->updated_at->format('j F Y') }}</time></p>
                @endif

                <x-content.prose :content="$content" />

                @if ($faqs->isNotEmpty())
                    <section class="mt-14" aria-labelledby="page-faqs">
                        <h2 id="page-faqs" class="mb-6 text-2xl font-extrabold sm:text-3xl">Questions people ask</h2>
                        <x-site.faq-list :faqs="$faqs" />
                    </section>
                @endif
            </div>
        </div>
    </x-ui.section>

    @unless ($isLegal)
        <x-site.contact-actions />
    @endunless
</x-layouts.app>
