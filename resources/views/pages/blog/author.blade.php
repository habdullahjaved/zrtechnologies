<x-layouts.app :seo="$seo">
    <x-site.page-header :title="$author->name" :eyebrow="$author->role_title ?? 'Author'" :breadcrumbs="$breadcrumbs">
        {{ $author->bio }}
    </x-site.page-header>

    <x-ui.section tone="white">
        <div class="grid gap-12 lg:grid-cols-[20rem_minmax(0,1fr)] lg:items-start">
            <x-ui.card padding="p-6" class="text-center">
                <img src="{{ $author->photoUrl() ?? asset('brand/zr-mark-colour-160w.webp') }}" alt="{{ $author->name }}" width="120" height="120" class="mx-auto size-28 rounded-full bg-ice-50 object-cover p-1">
                <p class="mt-4 font-heading text-xl font-bold text-navy-900">{{ $author->name }}</p>
                @if ($author->role_title)
                    <p class="text-sm font-semibold text-royal-600">{{ $author->role_title }}</p>
                @endif
                @if ($author->profileUrls() !== [])
                    <ul class="mt-4 flex flex-wrap justify-center gap-2">
                        @foreach ($author->profileUrls() as $url)
                            <li><a href="{{ $url }}" target="_blank" rel="noopener me" class="text-sm font-semibold text-royal-600 hover:underline">{{ parse_url($url, PHP_URL_HOST) }}</a></li>
                        @endforeach
                    </ul>
                @endif
            </x-ui.card>

            <div class="min-w-0">
                @if ($author->credentials)
                    <section aria-labelledby="experience">
                        <h2 id="experience" class="text-2xl font-extrabold">Experience</h2>
                        <div class="mt-4 space-y-4 leading-relaxed">
                            @foreach (preg_split('/\R{2,}/', trim($author->credentials)) as $paragraph)
                                <p>{{ $paragraph }}</p>
                            @endforeach
                        </div>
                    </section>
                @endif

                <section class="mt-12" aria-labelledby="author-posts">
                    <h2 id="author-posts" class="text-2xl font-extrabold">Guides by {{ $author->name }}</h2>
                    @if ($posts->isEmpty())
                        <p class="mt-4">No published guides yet.</p>
                    @else
                        <ul class="mt-6 grid gap-6 md:grid-cols-2">
                            @foreach ($posts as $post)
                                <li><x-site.post-card :post="$post" /></li>
                            @endforeach
                        </ul>
                        <div class="mt-10">{{ $posts->links() }}</div>
                    @endif
                </section>
            </div>
        </div>
    </x-ui.section>
</x-layouts.app>
