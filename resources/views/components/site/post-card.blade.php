@props([
    'post',
    'headingLevel' => 3,
])

@php($tag = 'h'.$headingLevel)

<article {{ $attributes->class('group flex h-full flex-col overflow-hidden rounded-card border border-ice-100 bg-white shadow-soft transition duration-300 hover:border-sky-200 hover:shadow-lift') }}>
    <a href="{{ $post->url() }}" class="block aspect-1200/630 overflow-hidden bg-ice-50" tabindex="-1" aria-hidden="true">
        <img
            src="{{ $post->featuredImageUrl() ?? asset('images/og/og-blog.jpg') }}"
            alt=""
            width="1200"
            height="630"
            loading="lazy"
            decoding="async"
            class="size-full object-cover transition duration-500 motion-safe:group-hover:scale-[1.03]"
        >
    </a>
    <div class="flex flex-1 flex-col p-6">
        <p class="text-sm font-semibold text-royal-600">{{ $post->category->name }}</p>
        <{{ $tag }} class="mt-2 text-xl leading-snug font-bold">
            <a href="{{ $post->url() }}" class="hover:text-royal-600">{{ $post->title }}</a>
        </{{ $tag }}>
        @if ($post->excerpt)
            <p class="mt-2 line-clamp-3 flex-1 leading-relaxed">{{ $post->excerpt }}</p>
        @endif
        <p class="mt-4 flex items-center gap-2 text-sm">
            <time datetime="{{ $post->published_at?->toDateString() }}">{{ $post->published_at?->format('j F Y') }}</time>
            <span aria-hidden="true">·</span>
            <span>{{ $post->reading_minutes }} min read</span>
        </p>
    </div>
</article>
