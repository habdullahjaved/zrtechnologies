<x-layouts.app :seo="$seo">
    <x-site.page-header title="Book Your iPhone Repair" :breadcrumbs="[['label' => 'Book a repair']]">
        Tell us where you are, what needs fixing and when suits you. It takes about two minutes, and you pay nothing until your iPhone comes back.
    </x-site.page-header>

    <x-ui.section tone="ice" spacing="tight">
        <livewire:booking-wizard />
    </x-ui.section>

    <x-site.contact-actions title="Rather talk to someone?" text="Call or WhatsApp us and we will book it in for you." />
</x-layouts.app>
