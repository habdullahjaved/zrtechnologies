<footer class="relative overflow-hidden bg-navy-950 text-slate-300" aria-labelledby="footer-heading">
    <h2 id="footer-heading" class="sr-only">Footer</h2>
    <x-ui.swoosh class="absolute inset-x-0 -top-10 h-48 w-full text-sky-400/40" />

    <div class="relative mx-auto max-w-7xl px-4 pt-16 pb-28 sm:px-6 lg:px-8 lg:pb-12">
        <div class="grid gap-12 lg:grid-cols-12">
            {{-- Brand + NAP --}}
            <div class="lg:col-span-4">
                <a href="{{ route('home') }}" class="inline-block" aria-label="{{ $business->trading_name }} home">
                    <img
                        src="{{ asset('brand/zr-logo-horizontal-for-dark-bg-240w.webp') }}"
                        srcset="{{ asset('brand/zr-logo-horizontal-for-dark-bg-240w.webp') }} 240w, {{ asset('brand/zr-logo-horizontal-for-dark-bg-480w.webp') }} 480w"
                        sizes="200px"
                        width="240"
                        height="62"
                        alt="{{ $business->trading_name }}"
                        class="h-12 w-auto"
                        loading="lazy"
                    >
                </a>
                <p class="mt-5 max-w-sm text-[0.95rem] leading-relaxed">{{ $business->short_description }}</p>

                <address class="mt-6 space-y-3 not-italic">
                    <a href="{{ $phoneHref }}" class="flex items-center gap-3 font-semibold text-white hover:text-sky-400" data-track="click_to_call">
                        <x-heroicon-s-phone class="size-5 text-sky-400" aria-hidden="true" />
                        {{ $phoneDisplay }}
                    </a>
                    <a href="{{ $whatsAppUrl }}" target="_blank" rel="noopener" class="flex items-center gap-3 font-semibold text-white hover:text-sky-400" data-track="click_whatsapp">
                        <x-icon.whatsapp class="size-5 text-sky-400" />
                        WhatsApp {{ $phoneDisplay }}
                    </a>
                    <a href="mailto:{{ $business->email }}" class="flex items-center gap-3 font-semibold text-white hover:text-sky-400">
                        <x-heroicon-s-envelope class="size-5 text-sky-400" aria-hidden="true" />
                        {{ $business->email }}
                    </a>
                    <p class="flex items-start gap-3">
                        <x-heroicon-s-map-pin class="mt-0.5 size-5 shrink-0 text-sky-400" aria-hidden="true" />
                        <span>Collection &amp; delivery across {{ $business->service_area_summary }}</span>
                    </p>
                </address>
            </div>

            {{-- Opening hours --}}
            <div class="lg:col-span-3">
                <h3 class="font-heading text-sm font-bold tracking-wider text-white uppercase">Opening hours</h3>
                <dl class="mt-5 space-y-2 text-[0.95rem]">
                    @foreach ($hours as $days => $time)
                        <div class="flex justify-between gap-4">
                            <dt>{{ $days }}</dt>
                            <dd @class(['font-semibold', 'text-white' => $time !== 'Closed', 'text-slate-400' => $time === 'Closed'])>{{ $time }}</dd>
                        </div>
                    @endforeach
                </dl>
                @if (filled($openingHours->note))
                    <p class="mt-4 text-sm text-slate-400">{{ $openingHours->note }}</p>
                @endif
            </div>

            {{-- Links --}}
            <div class="grid gap-10 sm:grid-cols-2 lg:col-span-5">
                <div>
                    <h3 class="font-heading text-sm font-bold tracking-wider text-white uppercase">Company</h3>
                    <ul class="mt-5 space-y-3 text-[0.95rem]">
                        <li><a href="{{ route('home') }}" class="hover:text-white">Home</a></li>
                        @foreach ($companyLinks as $link)
                            <li><a href="{{ $link['url'] }}" class="hover:text-white">{{ $link['label'] }}</a></li>
                        @endforeach
                        <li><a href="mailto:{{ $business->email }}" class="hover:text-white">Email us</a></li>
                    </ul>
                </div>

                @if ($socialLinks !== [])
                    <div>
                        <h3 class="font-heading text-sm font-bold tracking-wider text-white uppercase">Follow us</h3>
                        <ul class="mt-5 space-y-3 text-[0.95rem]">
                            @foreach ($socialLinks as $network)
                                <li><a href="{{ $network['url'] }}" target="_blank" rel="noopener me" class="hover:text-white">{{ $network['label'] }}</a></li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>

        @if ($serviceLinks !== [] || $areaLinks !== [])
            <div class="mt-12 grid gap-10 border-t border-white/10 pt-10 sm:grid-cols-2 lg:grid-cols-12">
                @if ($serviceLinks !== [])
                    <div class="lg:col-span-7">
                        <h3 class="font-heading text-sm font-bold tracking-wider text-white uppercase">iPhone repairs</h3>
                        <ul class="mt-5 grid gap-x-6 gap-y-3 text-[0.95rem] sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ($serviceLinks as $link)
                                <li><a href="{{ $link['url'] }}" class="hover:text-white">{{ $link['label'] }}</a></li>
                            @endforeach
                            @if (Route::has('prices'))
                                <li><a href="{{ route('prices') }}" class="font-semibold text-white hover:text-sky-400">All repair prices</a></li>
                            @endif
                        </ul>
                    </div>
                @endif

                @if ($areaLinks !== [])
                    <div class="lg:col-span-5">
                        <h3 class="font-heading text-sm font-bold tracking-wider text-white uppercase">Areas we cover</h3>
                        <ul class="mt-5 grid gap-x-6 gap-y-3 text-[0.95rem] sm:grid-cols-2">
                            @foreach ($areaLinks as $link)
                                <li><a href="{{ $link['url'] }}" class="hover:text-white">{{ $link['label'] }}</a></li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        @endif

        {{-- Company details + disclaimer --}}
        <div class="mt-14 border-t border-white/10 pt-8 text-sm leading-relaxed text-slate-400">
            @if ($legalLinks !== [])
                <nav aria-label="Legal" class="mb-6">
                    <ul class="flex flex-wrap gap-x-6 gap-y-2">
                        @foreach ($legalLinks as $link)
                            <li><a href="{{ $link['url'] }}" class="hover:text-white">{{ $link['label'] }}</a></li>
                        @endforeach
                    </ul>
                </nav>
            @endif

            <p>
                &copy; {{ now()->year }} {{ $business->legal_name }}. Registered in {{ $business->registered_in }}, company number {{ $business->company_number }}.
                Registered office: {{ $business->registeredOfficeOneLine() }}.
                @if (filled($business->vat_number))
                    VAT number {{ $business->vat_number }}.
                @endif
                @if (filled($business->ico_registration_number))
                    ICO registration {{ $business->ico_registration_number }}.
                @endif
            </p>
            <p class="mt-3">{{ $site->trademark_disclaimer }}</p>
        </div>
    </div>
</footer>
