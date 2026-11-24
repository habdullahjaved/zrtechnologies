<x-layouts.app :seo="$seo">
    <x-site.page-header :title="$page->title" :eyebrow="$page->eyebrow" :breadcrumbs="$breadcrumbs">
        {{ $page->intro }}
    </x-site.page-header>

    <x-ui.section tone="ice" spacing="tight">
        <div class="mx-auto max-w-3xl">
            <x-ui.card>
                <livewire:track-repair />
                <p class="mt-5 text-sm">
                    Can’t find your reference? It starts with ZR- and is in your booking confirmation email. Or call or message us and we will check for you.
                </p>
            </x-ui.card>
        </div>
    </x-ui.section>

    <x-site.contact-actions />
</x-layouts.app>
