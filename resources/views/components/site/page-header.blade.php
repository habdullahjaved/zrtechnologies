{{--
    Inner-page header: branded background, breadcrumbs, the page's single H1 and an intro.
    Pass breadcrumb items as for <x-ui.breadcrumbs>; the slot is the intro paragraph.
--}}
@props([
    'title',
    'breadcrumbs' => [],
    'eyebrow' => null,
])

<header {{ $attributes->class('relative isolate overflow-hidden bg-navy-900') }}>
    <x-ui.picture
        webp="images/hero/page-header-bg-1920x480.webp"
        fallback="images/hero/page-header-bg-1920x480.jpg"
        width="1920"
        height="480"
        class="absolute inset-0 -z-10"
        img-class="size-full object-cover"
    />
    <div class="absolute inset-0 -z-10 bg-navy-950/40"></div>

    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 sm:py-14 lg:px-8 lg:py-16">
        <x-ui.breadcrumbs :items="$breadcrumbs" tone="dark" />

        @if ($eyebrow)
            <p class="mt-6 text-sm font-semibold tracking-wider text-sky-400 uppercase">{{ $eyebrow }}</p>
        @endif

        <h1 @class(['max-w-3xl text-3xl font-extrabold text-white sm:text-4xl lg:text-5xl', 'mt-2' => $eyebrow, 'mt-6' => ! $eyebrow])>{{ $title }}</h1>

        @if ($slot->isNotEmpty())
            <div class="mt-4 max-w-2xl text-lg leading-relaxed text-sky-200">{{ $slot }}</div>
        @endif
    </div>
</header>
