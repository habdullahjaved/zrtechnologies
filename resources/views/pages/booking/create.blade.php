<x-layouts.app :seo="$seo">
    <x-site.page-header :title="$page->title" :eyebrow="$page->eyebrow" :breadcrumbs="$breadcrumbs" :compact="true">
        {{ $page->intro }}
    </x-site.page-header>

    <x-ui.section tone="ice" spacing="tight">
        <livewire:booking-wizard />
    </x-ui.section>

    <x-site.contact-actions title="Rather talk to someone?" text="Call or WhatsApp us and we will book it in for you." />
</x-layouts.app>
