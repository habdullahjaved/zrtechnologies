<x-layouts.app :seo="$seo">
    <x-site.page-header title="Book Your iPhone Repair" eyebrow="Choose a collection time" :breadcrumbs="[['label' => 'Book a repair']]">
        Choose your iPhone model and repair, then select a collection time that works for you.
    </x-site.page-header>

    <x-ui.section tone="ice" width="narrow">
        <x-ui.card padding="p-5 sm:p-8">
            <livewire:booking-wizard />
        </x-ui.card>
    </x-ui.section>
</x-layouts.app>
