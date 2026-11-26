<x-layouts.app :seo="$seo">
    <x-site.page-header :title="$page->title" :eyebrow="$page->eyebrow" :breadcrumbs="$breadcrumbs">
        {{ $page->intro }}
    </x-site.page-header>

    <x-ui.section tone="ice" spacing="tight">
        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_22rem] lg:items-start">
            <x-ui.card
                x-data="{ tab: new URLSearchParams(location.search).get('form') || (new URLSearchParams(location.search).has('model') || new URLSearchParams(location.search).has('repair') ? 'quote' : 'contact') }"
                padding="p-5 sm:p-8"
            >
                <div role="tablist" aria-label="How would you like us to help?" class="flex flex-wrap gap-2">
                    @foreach (['contact' => 'Send a message', 'callback' => 'Request a callback', 'quote' => 'Request repair details'] as $key => $label)
                        <button
                            type="button"
                            role="tab"
                            id="tab-{{ $key }}"
                            aria-controls="panel-{{ $key }}"
                            x-bind:aria-selected="tab === '{{ $key }}'"
                            x-bind:class="tab === '{{ $key }}' ? 'bg-royal-600 text-white border-royal-600' : 'bg-white text-navy-900 border-ice-100 hover:border-sky-200'"
                            x-on:click="tab = '{{ $key }}'"
                            class="rounded-full border px-4 py-2 text-sm font-semibold transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-400"
                        >{{ $label }}</button>
                    @endforeach
                </div>

                <div id="panel-contact" role="tabpanel" aria-labelledby="tab-contact" x-show="tab === 'contact'" class="mt-6">
                    <h2 class="text-2xl font-bold">Send us a message</h2>
                    <p class="mt-1.5 mb-6">Questions about a repair, an existing booking or anything else.</p>
                    <livewire:enquiry-form type="contact" />
                </div>
                <div id="panel-callback" role="tabpanel" aria-labelledby="tab-callback" x-show="tab === 'callback'" x-cloak class="mt-6">
                    <h2 class="text-2xl font-bold">Request a callback</h2>
                    <p class="mt-1.5 mb-6">Leave your number and tell us when suits you. We will call you back.</p>
                    <livewire:enquiry-form type="callback" />
                </div>
                <div id="panel-quote" role="tabpanel" aria-labelledby="tab-quote" x-show="tab === 'quote'" x-cloak class="mt-6">
                    <h2 class="text-2xl font-bold">Get a quote</h2>
                    <p class="mt-1.5 mb-6">Tell us your iPhone model, the service you need and your postcode. We’ll call you with the repair details and full cost.</p>
                    <livewire:enquiry-form type="quote" />
                </div>
            </x-ui.card>

            <aside aria-label="Contact details" class="grid gap-4">
                <x-ui.card padding="p-5">
                    <h2 class="text-lg font-bold">Talk to us</h2>
                    <ul class="mt-4 grid gap-3">
                        <li>
                            <a href="{{ \App\Support\Phone::telHref($business->phone) }}" data-track="click_to_call" class="flex items-center gap-3 font-semibold text-navy-900 hover:text-royal-600">
                                <span class="flex size-10 items-center justify-center rounded-xl bg-ice-50 text-royal-600"><x-heroicon-s-phone class="size-5" aria-hidden="true" /></span>
                                {{ \App\Support\Phone::display($business->phone) }}
                            </a>
                        </li>
                        <li>
                            <a href="{{ \App\Support\Phone::whatsAppUrl($business->whatsapp) }}" target="_blank" rel="noopener" data-track="click_whatsapp" class="flex items-center gap-3 font-semibold text-navy-900 hover:text-royal-600">
                                <span class="flex size-10 items-center justify-center rounded-xl bg-ice-50 text-whatsapp"><x-icon.whatsapp class="size-5" /></span>
                                WhatsApp us
                            </a>
                        </li>
                        <li>
                            <a href="mailto:{{ $business->email }}" class="flex items-center gap-3 font-semibold break-all text-navy-900 hover:text-royal-600">
                                <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-ice-50 text-royal-600"><x-heroicon-s-envelope class="size-5" aria-hidden="true" /></span>
                                {{ $business->email }}
                            </a>
                        </li>
                    </ul>
                </x-ui.card>

                <x-ui.card padding="p-5">
                    <h2 class="text-lg font-bold">Opening hours</h2>
                    <dl class="mt-3 grid gap-1.5 text-sm">
                        @foreach ($hours->summary() as $days => $time)
                            <div class="flex justify-between gap-3"><dt class="font-semibold text-navy-900">{{ $days }}</dt><dd>{{ $time }}</dd></div>
                        @endforeach
                    </dl>
                    @if ($hours->note)
                        <p class="mt-3 text-sm">{{ $hours->note }}</p>
                    @endif
                </x-ui.card>

                <x-ui.card padding="p-5">
                    <h2 class="text-lg font-bold">Where we work</h2>
                    <p class="mt-2 text-sm leading-relaxed">{{ $business->service_area_summary }}</p>
                    <div class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-sm font-semibold">
                        <a href="{{ route('areas.index') }}" class="text-royal-600 hover:underline">Areas we cover</a>
                        <a href="{{ route('track-repair') }}" class="text-royal-600 hover:underline">Track a repair</a>
                    </div>
                </x-ui.card>
            </aside>
        </div>
    </x-ui.section>
</x-layouts.app>
