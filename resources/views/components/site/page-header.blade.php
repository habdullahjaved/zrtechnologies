{{--
    Inner-page header: branded background, breadcrumbs, the page's single H1 and an intro.
    Pass breadcrumb items as for <x-ui.breadcrumbs>; the slot is the intro paragraph.
--}}
@props([
    'title',
    'breadcrumbs' => [],
    'eyebrow' => null,
    // Shorter header (smaller heading, intro hidden on phones) so a form starts near the top.
    'compact' => false,
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

    <div @class(['mx-auto max-w-7xl px-4 sm:px-6 lg:px-8', 'py-10 sm:py-14 lg:py-16' => ! $compact, 'py-5 sm:py-10' => $compact])>
        <x-ui.breadcrumbs :items="$breadcrumbs" tone="dark" @class(['hidden sm:block' => $compact]) />

        @if ($eyebrow)
            <p class="mt-6 text-sm font-semibold tracking-wider text-sky-400 uppercase">{{ $eyebrow }}</p>
        @endif

        <h1 @class(['max-w-3xl font-extrabold text-white sm:text-4xl lg:text-5xl', 'text-3xl' => ! $compact, 'text-2xl' => $compact, 'mt-2' => $eyebrow, 'mt-6' => ! $eyebrow && ! $compact, 'sm:mt-6' => ! $eyebrow && $compact])>{{ $title }}</h1>

        @if ($slot->isNotEmpty())
            <div @class(['mt-4 max-w-2xl text-lg leading-relaxed text-sky-200', 'hidden sm:block' => $compact])>{{ $slot }}</div>
        @endif
    </div>
</header>
