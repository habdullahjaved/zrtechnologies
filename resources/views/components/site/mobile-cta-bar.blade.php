<nav
    aria-label="Quick contact"
    class="fixed inset-x-0 bottom-0 z-50 border-t border-ice-100 bg-white/95 px-3 pt-2 pb-[max(0.5rem,env(safe-area-inset-bottom))] shadow-[0_-8px_30px_-12px_rgb(10_31_78/0.25)] backdrop-blur lg:hidden"
>
    <ul class="grid grid-cols-3 gap-2">
        <li>
            <a href="{{ $phoneHref }}" class="flex h-14 flex-col items-center justify-center gap-0.5 rounded-xl text-xs font-semibold text-navy-900 hover:bg-ice-50" data-track="click_to_call">
                <x-heroicon-s-phone class="size-5 text-royal-600" aria-hidden="true" />
                Call
            </a>
        </li>
        <li>
            <a href="{{ $whatsAppUrl }}" target="_blank" rel="noopener" class="flex h-14 flex-col items-center justify-center gap-0.5 rounded-xl text-xs font-semibold text-navy-900 hover:bg-ice-50" data-track="click_whatsapp">
                <x-icon.whatsapp class="size-5 text-whatsapp" />
                WhatsApp
            </a>
        </li>
        <li>
            <a
                href="{{ $bookingUrl }}"
                @if ($bookingOpensWhatsApp) target="_blank" rel="noopener" @endif
                class="flex h-14 flex-col items-center justify-center gap-0.5 rounded-xl bg-royal-600 text-xs font-semibold text-white shadow-soft hover:bg-blue-500"
            >
                <x-heroicon-s-wrench-screwdriver class="size-5" aria-hidden="true" />
                Book Repair
            </a>
        </li>
    </ul>
</nav>
