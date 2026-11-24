<x-layouts.app :seo="$seo">
    <x-site.page-header :title="$page->title" :eyebrow="$page->eyebrow" :breadcrumbs="$breadcrumbs">
        {{ $page->intro }}
    </x-site.page-header>

    <x-ui.section tone="ice">
        @if ($topics === [])
            <p class="text-center">Our answers are being updated. Please call or message us with your question.</p>
        @else
            <div class="grid gap-10 lg:grid-cols-[16rem_minmax(0,1fr)] lg:items-start">
                <nav aria-label="FAQ topics" class="rounded-card border border-ice-100 bg-white p-5 lg:sticky lg:top-28">
                    <p class="font-heading text-sm font-bold tracking-wider text-navy-900 uppercase">Topics</p>
                    <ul class="mt-3 space-y-2 text-[0.95rem]">
                        @foreach ($topics as ['topic' => $topic])
                            <li><a href="#{{ $topic->value }}" class="text-royal-600 hover:underline">{{ $topic->getLabel() }}</a></li>
                        @endforeach
                    </ul>
                </nav>

                <div class="space-y-12">
                    @foreach ($topics as ['topic' => $topic, 'faqs' => $faqs])
                        <section id="{{ $topic->value }}" class="scroll-mt-28" aria-labelledby="{{ $topic->value }}-heading">
                            <h2 id="{{ $topic->value }}-heading" class="mb-5 text-2xl font-extrabold sm:text-3xl">{{ $topic->getLabel() }}</h2>
                            <x-site.faq-list :faqs="$faqs" />
                        </section>
                    @endforeach
                </div>
            </div>
        @endif
    </x-ui.section>

    <x-site.contact-actions title="Still have a question?" text="Call or WhatsApp us and we will be happy to help." />
</x-layouts.app>
