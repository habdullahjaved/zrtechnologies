<x-layouts.app :seo="$seo">
    {{-- Booking conversion for the browser tags (sent once, same event ID as the server-side send). --}}
    <div hidden data-zr-event="{{ json_encode($trackingEvent) }}"></div>

    <x-site.page-header title="You’re booked in" :breadcrumbs="[['label' => 'Book a repair', 'url' => route('booking.create')], ['label' => 'Booking confirmed']]">
        Thanks, {{ $job->customer->first_name }}. Your booking reference is <strong class="text-white">{{ $job->reference }}</strong>.
        @if ($job->customer->email)
            We have emailed your confirmation to {{ $job->customer->email }}.
        @endif
    </x-site.page-header>

    <x-ui.section tone="ice" spacing="tight">
        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_22rem] lg:items-start">
            <x-ui.card>
                <h2 class="text-2xl font-bold">What happens next</h2>
                <ol class="mt-6 grid gap-5">
                    @foreach ([
                        ['icon' => 'heroicon-o-device-phone-mobile', 'title' => 'Get your iPhone ready', 'text' => $passcodeNotice],
                        ['icon' => 'heroicon-o-truck', 'title' => 'We collect it', 'text' => 'Our driver collects your iPhone '.($job->collectionSlotLabel() ? 'on '.$job->collectionSlotLabel() : 'at the time we agree').' and texts you when they are on the way.'],
                        ['icon' => 'heroicon-o-wrench-screwdriver', 'title' => 'We repair it', 'text' => 'A technician checks and repairs your iPhone. We contact you if anything needs discussing.'],
                        ['icon' => 'heroicon-o-home', 'title' => 'We bring it back', 'text' => $paymentNote],
                    ] as $index => $stage)
                        <li class="flex gap-4">
                            <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-ice-50 text-royal-600">
                                <x-dynamic-component :component="$stage['icon']" class="size-6" aria-hidden="true" />
                            </span>
                            <div>
                                <h3 class="font-bold">{{ $index + 1 }}. {{ $stage['title'] }}</h3>
                                <p class="mt-1 leading-relaxed">{{ $stage['text'] }}</p>
                            </div>
                        </li>
                    @endforeach
                </ol>

                <div class="mt-8 flex flex-wrap gap-3">
                    <x-ui.button :href="$trackUrl">
                        <x-heroicon-s-map class="size-4" aria-hidden="true" />
                        Track your repair
                    </x-ui.button>
                    @if ($googleCalendarUrl)
                        <x-ui.button :href="$googleCalendarUrl" variant="secondary" :external="true">
                            <x-heroicon-o-calendar-days class="size-4" aria-hidden="true" />
                            Add to Google Calendar
                        </x-ui.button>
                        <x-ui.button :href="$calendarUrl" variant="secondary">
                            <x-heroicon-o-arrow-down-tray class="size-4" aria-hidden="true" />
                            Add to Apple or Outlook calendar
                        </x-ui.button>
                    @endif
                    <x-ui.button :href="$whatsAppUrl" variant="whatsapp" :external="true">
                        <x-icon.whatsapp class="size-4" />
                        Message us
                    </x-ui.button>
                </div>
            </x-ui.card>

            <aside aria-label="Your booking">
                <x-ui.card padding="p-5">
                    <h2 class="text-lg font-bold">Your booking</h2>
                    <dl class="mt-4 grid gap-3 text-sm">
                        <div><dt class="font-semibold text-navy-900">Reference</dt><dd>{{ $job->reference }}</dd></div>
                        <div><dt class="font-semibold text-navy-900">Device</dt><dd>{{ collect([$job->deviceModel?->name, $job->device_colour, $job->device_storage])->filter()->implode(', ') }}</dd></div>
                        <div>
                            <dt class="font-semibold text-navy-900">Repairs</dt>
                            <dd>
                                @foreach ($job->items as $item)
                                    <div>{{ $item->description }}</div>
                                @endforeach
                            </dd>
                        </div>
                        @if ($job->collectionSlotLabel())
                            <div><dt class="font-semibold text-navy-900">Collection</dt><dd>{{ $job->collectionSlotLabel() }}</dd></div>
                        @endif
                        @if ($job->collectionAddress)
                            <div><dt class="font-semibold text-navy-900">Address</dt><dd>{{ $job->collectionAddress->oneLine() }}</dd></div>
                        @endif
                    </dl>
                    <div class="mt-4 flex items-baseline justify-between border-t border-ice-100 pt-4">
                        <span class="font-bold text-navy-900">Repair details</span>
                        <span>We’ll confirm these with you directly.</span>
                    </div>
                </x-ui.card>
            </aside>
        </div>
    </x-ui.section>
</x-layouts.app>
