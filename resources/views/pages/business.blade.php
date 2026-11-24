<x-layouts.app :seo="$seo">
    <x-site.page-header title="iPhone Repairs for Business" eyebrow="Businesses, schools and organisations" :breadcrumbs="[['label' => 'Business repairs']]">
        Keep your team’s iPhones working. We collect from your site, repair the devices and bring them back, with one point of contact for every job.
    </x-site.page-header>

    <x-ui.section tone="white">
        <div class="grid gap-10 lg:grid-cols-[minmax(0,1fr)_28rem] lg:items-start">
            <div>
                <x-ui.section-heading eyebrow="How it works for organisations" title="Repairs without the downtime" align="left">
                    Whether it is one cracked screen or a drawer of phones with tired batteries, tell us what you have and we will plan the work around you.
                </x-ui.section-heading>

                <ul class="mt-10 grid gap-5 sm:grid-cols-2">
                    @foreach ([
                        ['icon' => 'heroicon-o-truck', 'title' => 'Collection from your site', 'text' => 'We collect from your office, school or depot within our collection area and return the devices when they are done.'],
                        ['icon' => 'heroicon-o-clipboard-document-list', 'title' => 'One quote for the batch', 'text' => 'Send us the models and faults and we will quote for the whole batch before any work starts.'],
                        ['icon' => 'heroicon-o-shield-check', 'title' => 'Careful with your data', 'text' => 'We never ask for passcodes in advance. Devices stay with our team from collection to return.'],
                        ['icon' => 'heroicon-o-document-text', 'title' => 'Clear paperwork', 'text' => 'Each device gets its own job reference, so you can track every repair and match it to your records.'],
                    ] as $benefit)
                        <li class="rounded-card border border-ice-100 bg-ice-50 p-5">
                            <x-dynamic-component :component="$benefit['icon']" class="size-7 text-royal-600" aria-hidden="true" />
                            <h3 class="mt-3 text-lg font-bold">{{ $benefit['title'] }}</h3>
                            <p class="mt-1.5 leading-relaxed">{{ $benefit['text'] }}</p>
                        </li>
                    @endforeach
                </ul>
            </div>

            <x-ui.card id="business-enquiry" class="scroll-mt-28">
                <h2 class="text-2xl font-bold">Tell us what you need</h2>
                <p class="mt-1.5 mb-6">Send us the details and we will come back to you with a quote.</p>
                <livewire:enquiry-form type="business" />
            </x-ui.card>
        </div>
    </x-ui.section>

    <x-site.contact-actions title="Prefer to talk it through?" text="Call or WhatsApp us and we will help you plan the repairs." />
</x-layouts.app>
