@if (filled($announcement))
    <div class="bg-navy-950 px-4 py-2 text-center text-sm font-medium text-white">
        {{ $announcement }}
    </div>
@endif

<header
    x-data="stickyHeader"
    :class="scrolled ? 'shadow-soft bg-white/95' : 'bg-white'"
    class="sticky top-0 z-40 border-b border-ice-100 bg-white backdrop-blur supports-backdrop-filter:bg-white/90"
>
    <div x-data="mobileNav" @keydown.escape.window="close()" class="mx-auto flex h-16 max-w-7xl items-center gap-6 px-4 sm:px-6 lg:h-20 lg:px-8">
        <a href="{{ route('home') }}" class="shrink-0 rounded-md" aria-label="{{ $tradingName }} home">
            <img
                src="{{ asset('brand/zr-logo-horizontal-colour-240w.webp') }}"
                srcset="{{ asset('brand/zr-logo-horizontal-colour-240w.webp') }} 240w, {{ asset('brand/zr-logo-horizontal-colour-480w.webp') }} 480w"
                sizes="(min-width: 1024px) 186px, 156px"
                width="240"
                height="62"
                alt="{{ $tradingName }}"
                class="h-10 w-auto lg:h-12"
                fetchpriority="high"
            >
        </a>

        @if ($links !== [] || $megaMenu !== null)
            <nav aria-label="Main" class="hidden flex-1 lg:block">
                <ul class="flex items-center justify-center gap-1">
                    @if ($megaMenu !== null)
                        <li x-data="megaMenu" @mouseenter="enter()" @mouseleave="leave()" @keydown.escape="close(true)" @focusout="focusOut($event)" @click.outside="close()">
                            <button
                                type="button"
                                x-ref="button"
                                @click="toggle()"
                                :aria-expanded="open.toString()"
                                aria-controls="mega-menu"
                                @class([
                                    'flex items-center gap-1 rounded-lg px-3 py-2 text-[0.95rem] font-semibold hover:bg-ice-50 hover:text-royal-600',
                                    'text-royal-600' => $megaMenu['active'],
                                    'text-navy-900' => ! $megaMenu['active'],
                                ])
                            >
                                iPhone Repair
                                <x-heroicon-m-chevron-down class="size-4 transition" ::class="open && 'rotate-180'" aria-hidden="true" />
                            </button>

                            <div
                                id="mega-menu"
                                x-show="open"
                                x-cloak
                                x-transition:enter="transition duration-150 ease-out"
                                x-transition:enter-start="opacity-0 -translate-y-1"
                                x-transition:enter-end="opacity-100 translate-y-0"
                                class="absolute inset-x-0 top-full border-b border-ice-100 bg-white shadow-lift"
                            >
                                <div class="mx-auto grid max-w-7xl gap-10 px-8 py-8 lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
                                    <div>
                                        <p class="text-xs font-semibold tracking-wider text-ink uppercase">Repairs</p>
                                        @if ($megaMenu['services'] !== [])
                                            <ul class="mt-4 grid grid-cols-2 gap-1 xl:grid-cols-3">
                                                @foreach ($megaMenu['services'] as $service)
                                                    <li>
                                                        <a href="{{ $service['url'] }}" class="flex items-center gap-3 rounded-xl p-2.5 hover:bg-ice-50">
                                                            @if ($service['icon'])
                                                                <img src="{{ asset("images/icons/icon-{$service['icon']}-navy-96.webp") }}" alt="" width="40" height="40" loading="lazy" class="size-10 shrink-0 rounded-lg bg-ice-50 p-1.5">
                                                            @endif
                                                            <span>
                                                                <span class="block font-semibold text-navy-900">{{ $service['label'] }}</span>
                                                                @if ($service['from'])
                                                                    <span class="block text-sm">from {{ $service['from'] }}</span>
                                                                @endif
                                                            </span>
                                                        </a>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @endif
                                        <a href="{{ $megaMenu['url'] }}" class="mt-5 inline-flex items-center gap-1.5 font-semibold text-royal-600 hover:underline">
                                            All iPhone repairs
                                            <x-heroicon-m-arrow-right class="size-4" aria-hidden="true" />
                                        </a>
                                    </div>

                                    <div class="rounded-card bg-ice-50 p-6">
                                        @if ($megaMenu['models'] !== [])
                                            <p class="text-xs font-semibold tracking-wider text-ink uppercase">Popular models</p>
                                            <ul class="mt-4 grid grid-cols-2 gap-x-4 gap-y-2">
                                                @foreach ($megaMenu['models'] as $model)
                                                    <li><a href="{{ $model['url'] }}" class="font-medium text-navy-900 hover:text-royal-600">{{ $model['label'] }}</a></li>
                                                @endforeach
                                            </ul>
                                        @endif
                                        @if ($megaMenu['prices_url'])
                                            <x-ui.button :href="$megaMenu['prices_url']" variant="secondary" size="md" class="mt-6 w-full">
                                                See all repair prices
                                            </x-ui.button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </li>
                    @endif
                    @foreach ($links as $link)
                        <li>
                            <a
                                href="{{ $link['url'] }}"
                                @class([
                                    'rounded-lg px-3 py-2 text-[0.95rem] font-semibold hover:bg-ice-50 hover:text-royal-600',
                                    'text-royal-600' => $link['active'],
                                    'text-navy-900' => ! $link['active'],
                                ])
                                @if ($link['active']) aria-current="page" @endif
                            >{{ $link['label'] }}</a>
                        </li>
                    @endforeach
                </ul>
            </nav>
        @else
            <div class="hidden flex-1 lg:block"></div>
        @endif

        <div class="ml-auto hidden items-center gap-5 lg:flex">
            <a href="{{ $phoneHref }}" class="group flex items-center gap-2 font-semibold text-navy-900 hover:text-royal-600" data-track="click_to_call">
                <span class="flex size-9 items-center justify-center rounded-full bg-ice-50 text-royal-600 group-hover:bg-ice-100">
                    <x-heroicon-s-phone class="size-4" aria-hidden="true" />
                </span>
                <span class="leading-tight">
                    <span class="block text-xs font-medium text-ink">Call or WhatsApp</span>
                    {{ $phoneDisplay }}
                </span>
            </a>
            <x-ui.button :href="$bookingUrl" size="md" :external="$bookingOpensWhatsApp">
                Book a Repair
            </x-ui.button>
        </div>

        {{-- Mobile controls --}}
        <div class="ml-auto flex items-center gap-2 lg:hidden">
            <a href="{{ $phoneHref }}" class="flex size-11 items-center justify-center rounded-full bg-ice-50 text-royal-600" aria-label="Call {{ $phoneDisplay }}" data-track="click_to_call">
                <x-heroicon-s-phone class="size-5" aria-hidden="true" />
            </a>
            <button
                type="button"
                x-ref="toggle"
                @click="toggle()"
                :aria-expanded="open.toString()"
                aria-controls="mobile-menu"
                class="flex size-11 items-center justify-center rounded-full text-navy-900 hover:bg-ice-50"
            >
                <span class="sr-only">Menu</span>
                <x-heroicon-o-bars-3 x-show="! open" class="size-6" aria-hidden="true" />
                <x-heroicon-o-x-mark x-show="open" x-cloak class="size-6" aria-hidden="true" />
            </button>
        </div>

        {{-- Mobile drawer --}}
        <div
            id="mobile-menu"
            x-ref="panel"
            x-show="open"
            x-cloak
            x-transition:enter="transition duration-200 ease-out"
            x-transition:enter-start="opacity-0 -translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition duration-150 ease-in"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-x-0 top-16 bottom-0 z-40 overflow-y-auto bg-white px-4 pt-4 pb-28 lg:hidden"
        >
            @if ($links !== [] || $megaMenu !== null)
                <nav aria-label="Mobile">
                    <ul class="divide-y divide-ice-100">
                        @if ($megaMenu !== null)
                            <li x-data="{ expanded: false }">
                                <button type="button" @click="expanded = ! expanded" :aria-expanded="expanded.toString()" aria-controls="mobile-repairs" class="flex w-full items-center justify-between py-4 font-heading text-lg font-bold text-navy-900">
                                    iPhone Repair
                                    <x-heroicon-o-chevron-down class="size-5 text-royal-600 transition" ::class="expanded && 'rotate-180'" aria-hidden="true" />
                                </button>
                                <ul id="mobile-repairs" x-show="expanded" x-cloak class="grid gap-1 pb-4">
                                    @foreach ($megaMenu['services'] as $service)
                                        <li>
                                            <a href="{{ $service['url'] }}" @click="close()" class="flex items-center justify-between rounded-lg px-3 py-2.5 font-semibold text-navy-900 hover:bg-ice-50">
                                                {{ $service['label'] }}
                                                @if ($service['from'])
                                                    <span class="text-sm font-normal text-ink">from {{ $service['from'] }}</span>
                                                @endif
                                            </a>
                                        </li>
                                    @endforeach
                                    <li>
                                        <a href="{{ $megaMenu['url'] }}" @click="close()" class="block rounded-lg px-3 py-2.5 font-semibold text-royal-600 hover:bg-ice-50">All iPhone repairs</a>
                                    </li>
                                </ul>
                            </li>
                        @endif
                        @foreach ($links as $link)
                            <li>
                                <a href="{{ $link['url'] }}" @click="close()" class="flex items-center justify-between py-4 font-heading text-lg font-bold text-navy-900">
                                    {{ $link['label'] }}
                                    <x-heroicon-o-chevron-right class="size-5 text-royal-600" aria-hidden="true" />
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </nav>
            @endif

            <div class="mt-6 grid gap-3">
                <x-ui.button :href="$bookingUrl" size="lg" class="w-full" :external="$bookingOpensWhatsApp">
                    Book a Repair
                </x-ui.button>
                <x-ui.button :href="$phoneHref" variant="secondary" size="lg" class="w-full" data-track="click_to_call">
                    <x-heroicon-s-phone class="size-5" aria-hidden="true" />
                    Call {{ $phoneDisplay }}
                </x-ui.button>
                <x-ui.button :href="$whatsAppUrl" variant="whatsapp" size="lg" class="w-full" target="_blank" rel="noopener" data-track="click_whatsapp">
                    <x-icon.whatsapp class="size-5" />
                    WhatsApp us
                </x-ui.button>
            </div>
        </div>
    </div>
</header>
