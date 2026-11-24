<x-layouts.app :seo="$seo">
    <x-slot:head>
        <link rel="preload" as="image" type="image/webp" href="{{ asset('images/hero/hero-home-mobile-1080x1350.webp') }}" media="(max-width: 767px)" fetchpriority="high">
        <link rel="preload" as="image" type="image/webp" href="{{ asset('images/hero/hero-home-desktop-2400x1200.webp') }}" media="(min-width: 768px)" fetchpriority="high">
    </x-slot:head>

    {{-- Hero --}}
    <section class="relative isolate overflow-hidden bg-navy-900">
        <x-ui.picture
            webp="images/hero/hero-home-desktop-2400x1200.webp"
            fallback="images/hero/hero-home-desktop-2400x1200.jpg"
            mobile-webp="images/hero/hero-home-mobile-1080x1350.webp"
            mobile-fallback="images/hero/hero-home-mobile-1080x1350.jpg"
            width="2400"
            height="1200"
            :priority="true"
            class="absolute inset-0 -z-10"
            img-class="size-full object-cover object-bottom md:object-right"
        />
        <div class="absolute inset-0 -z-10 bg-linear-to-b from-navy-950/70 via-navy-900/30 to-transparent md:bg-linear-to-r md:from-navy-950/80 md:via-navy-900/40 md:to-transparent"></div>

        <div class="mx-auto max-w-7xl px-4 pt-12 pb-108 sm:px-6 sm:pb-136 md:pt-24 md:pb-28 lg:px-8 lg:pt-28 lg:pb-36">
            <div class="max-w-xl motion-safe:animate-fade-up lg:max-w-2xl">
                <x-ui.badge tone="on-dark">
                    <x-heroicon-s-map-pin class="size-3.5" aria-hidden="true" />
                    Blackburn &amp; East Lancashire
                </x-ui.badge>

                <h1 class="mt-5 text-4xl leading-[1.08] font-extrabold text-white sm:text-5xl lg:text-6xl">
                    Expert iPhone Repair in Blackburn <span class="text-sky-400">We Collect, Repair &amp; Return</span>
                </h1>

                <p class="mt-6 max-w-lg text-lg leading-relaxed text-sky-200">
                    No trips, no queues. Book online, we pick up your iPhone from home or work, repair it with quality parts and bring it straight back, with a warranty on every repair.
                </p>

                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                    <x-ui.button :href="$bookingUrl" size="lg" :external="$bookingOpensWhatsApp">
                        <x-heroicon-s-wrench-screwdriver class="size-5" aria-hidden="true" />
                        Book a Repair
                    </x-ui.button>
                    <x-ui.button :href="$phoneHref" variant="outline-light" size="lg" data-track="click_to_call">
                        <x-heroicon-s-phone class="size-5" aria-hidden="true" />
                        Call {{ $phoneDisplay }}
                    </x-ui.button>
                </div>

                <div class="mt-8 max-w-lg">
                    <livewire:postcode-checker variant="hero" id-prefix="hero-postcode" />
                </div>

                <ul class="mt-10 grid grid-cols-2 gap-x-6 gap-y-4 text-sm font-semibold text-white sm:max-w-lg">
                    @foreach ([
                        ['icon' => 'heroicon-s-truck', 'label' => 'Collection & delivery'],
                        ['icon' => 'heroicon-s-shield-check', 'label' => 'Warranty on every repair'],
                        ['icon' => 'heroicon-s-bolt', 'label' => 'Fast turnaround'],
                        ['icon' => 'heroicon-s-check-badge', 'label' => 'Quality-tested parts'],
                    ] as $badge)
                        <li class="flex items-center gap-2.5">
                            <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-white/10 ring-1 ring-white/20">
                                <x-dynamic-component :component="$badge['icon']" class="size-4 text-sky-400" aria-hidden="true" />
                            </span>
                            {{ $badge['label'] }}
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </section>

    {{-- Instant price checker --}}
    <x-ui.section tone="ice">
        <x-ui.section-heading eyebrow="Instant prices" title="Get your repair price in seconds">
            Choose your iPhone to see the price of every repair, including parts and labour.
        </x-ui.section-heading>

        <div class="mx-auto mt-12 max-w-5xl">
            <livewire:price-checker id-prefix="home-price-checker" />
        </div>
    </x-ui.section>

    {{-- How it works --}}
    <x-ui.section tone="white">
        <x-ui.section-heading eyebrow="How it works" title="Your iPhone fixed without leaving home">
            Four simple steps from booking to having your phone back in your hand.
        </x-ui.section-heading>

        <ol class="mt-14 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                ['icon' => 'heroicon-o-calendar-days', 'title' => 'Book online', 'text' => 'Choose your iPhone model, tell us what is wrong and pick a collection time that suits you.'],
                ['icon' => 'heroicon-o-truck', 'title' => 'We collect', 'text' => 'Our technician collects your iPhone from your home or workplace at the agreed time.'],
                ['icon' => 'heroicon-o-wrench-screwdriver', 'title' => 'Expert repair', 'text' => 'We diagnose and repair it with quality parts, then test every function before it leaves us.'],
                ['icon' => 'heroicon-o-hand-thumb-up', 'title' => 'We return it', 'text' => 'Your iPhone comes back to your door, working as it should and covered by our warranty.'],
            ] as $step)
                <li class="relative">
                    <x-ui.card class="h-full" :interactive="true">
                        <div class="flex items-center gap-4">
                            <span class="flex size-12 items-center justify-center rounded-2xl bg-ice-50 text-royal-600 ring-1 ring-ice-100">
                                <x-dynamic-component :component="$step['icon']" class="size-6" aria-hidden="true" />
                            </span>
                            <span class="font-heading text-sm font-extrabold tracking-wider text-royal-600">STEP {{ $loop->iteration }}</span>
                        </div>
                        <h3 class="mt-5 text-xl font-bold">{{ $step['title'] }}</h3>
                        <p class="mt-2 leading-relaxed">{{ $step['text'] }}</p>
                    </x-ui.card>
                </li>
            @endforeach
        </ol>
    </x-ui.section>

    {{-- Popular repairs --}}
    @if ($popularRepairs !== [])
        <x-ui.section tone="white" class="pt-0 sm:pt-0 lg:pt-0">
            <x-ui.section-heading eyebrow="Popular repairs" title="The repairs we do most">
                Clear prices for every model. Choose your repair to get started.
            </x-ui.section-heading>

            <ul class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($popularRepairs as ['repair' => $repair, 'from_pence' => $fromPence])
                    <li>
                        <x-ui.card class="flex h-full flex-col" :interactive="true">
                            @if ($repair->icon)
                                <img src="{{ asset("images/icons/icon-{$repair->icon}-navy-96.webp") }}" alt="" width="56" height="56" loading="lazy" class="size-14 rounded-2xl bg-ice-50 p-2">
                            @endif
                            <h3 class="mt-5 text-xl font-bold">{{ $repair->name }}</h3>
                            @if ($repair->summary)
                                <p class="mt-2 flex-1 leading-relaxed">{{ $repair->summary }}</p>
                            @endif
                            <p class="mt-5 flex items-baseline gap-1.5">
                                <span class="text-sm">from</span>
                                <span class="font-heading text-2xl font-extrabold text-navy-900">{{ \App\Support\Money::format($fromPence) }}</span>
                            </p>
                        </x-ui.card>
                    </li>
                @endforeach
            </ul>

            @if (Route::has('prices'))
                <div class="mt-10 text-center">
                    <x-ui.button :href="route('prices')" variant="secondary">
                        See all repair prices
                        <x-heroicon-m-arrow-right class="size-4" aria-hidden="true" />
                    </x-ui.button>
                </div>
            @endif
        </x-ui.section>
    @endif

    {{-- Why ZR Technologies --}}
    <x-ui.section tone="ice">
        <div class="grid items-center gap-12 lg:grid-cols-2">
            <div>
                <x-ui.section-heading align="left" eyebrow="Why ZR Technologies" title="Repairs you can trust, on your schedule">
                    We are a local Blackburn business focused on one thing: getting your iPhone working again with as little hassle as possible.
                </x-ui.section-heading>
            </div>

            <ul class="grid gap-5 sm:grid-cols-2">
                @foreach ([
                    ['icon' => 'heroicon-o-home-modern', 'title' => 'We come to you', 'text' => 'Collection and return to your home or workplace. No travelling, no waiting around in a shop.'],
                    ['icon' => 'heroicon-o-shield-check', 'title' => 'Warranty included', 'text' => 'Every repair is covered by our warranty, so you are protected if anything is not right.'],
                    ['icon' => 'heroicon-o-lock-closed', 'title' => 'Your data stays private', 'text' => 'We never ask for your passcode in a form, and we treat your phone and data with care.'],
                    ['icon' => 'heroicon-o-currency-pound', 'title' => 'Clear prices first', 'text' => 'You see the price before you book. If anything changes after diagnosis, we ask before we carry on.'],
                ] as $benefit)
                    <li class="flex gap-4 rounded-card bg-white p-6 shadow-soft ring-1 ring-ice-100">
                        <x-dynamic-component :component="$benefit['icon']" class="size-7 shrink-0 text-royal-600" aria-hidden="true" />
                        <div>
                            <h3 class="text-lg font-bold">{{ $benefit['title'] }}</h3>
                            <p class="mt-1.5 leading-relaxed">{{ $benefit['text'] }}</p>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    </x-ui.section>

    <x-site.contact-actions />
</x-layouts.app>
