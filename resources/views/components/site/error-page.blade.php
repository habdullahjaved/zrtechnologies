{{-- Branded error page inside the full site layout (for errors where the app is healthy). --}}
@props([
    'code',
    'title',
    'message',
])

<x-layouts.app :seo="new \App\Support\Seo\SeoData(title: $title, noindex: true)">
    <x-ui.section tone="ice" class="min-h-[60vh]">
        <div class="mx-auto max-w-2xl py-8 text-center">
            <p class="font-heading text-7xl font-extrabold text-royal-600 sm:text-8xl">{{ $code }}</p>
            <h1 class="mt-6 text-3xl font-extrabold sm:text-4xl">{{ $title }}</h1>
            <p class="mt-4 text-lg leading-relaxed">{{ $message }}</p>
            <div class="mt-10 flex flex-col justify-center gap-3 sm:flex-row">
                <x-ui.button :href="route('home')" size="lg">
                    <x-heroicon-s-home class="size-5" aria-hidden="true" />
                    Back to the home page
                </x-ui.button>
                {{ $slot }}
            </div>
        </div>
    </x-ui.section>
</x-layouts.app>
