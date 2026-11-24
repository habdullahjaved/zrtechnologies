<x-layouts.app :seo="$seo">
    <x-site.page-header :title="$page->title" :eyebrow="$page->eyebrow" :breadcrumbs="$breadcrumbs">
        {{ $page->intro }}
    </x-site.page-header>

    <x-ui.section tone="ice">
        @if ($videos->isEmpty())
            <p class="text-center">New videos are on the way. Follow us on social media to see them first.</p>
        @else
            <ul class="grid grid-cols-2 gap-5 sm:gap-6 md:grid-cols-3 lg:grid-cols-4">
                @foreach ($videos as $video)
                    <li><x-site.video-card :video="$video" :heading-level="2" /></li>
                @endforeach
            </ul>
        @endif
    </x-ui.section>

    <x-site.contact-actions />
</x-layouts.app>
