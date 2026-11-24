<section class="relative isolate overflow-hidden bg-navy-900">
    <x-ui.picture
        webp="images/hero/cta-band-bg-1920x400.webp"
        fallback="images/hero/cta-band-bg-1920x400.jpg"
        width="1920"
        height="400"
        class="absolute inset-0 -z-10"
        img-class="size-full object-cover"
    />
    <div class="absolute inset-0 -z-10 bg-linear-to-r from-navy-950/85 via-navy-900/60 to-transparent"></div>

    <div class="mx-auto flex max-w-7xl flex-col gap-8 px-4 py-14 sm:px-6 lg:flex-row lg:items-center lg:justify-between lg:px-8 lg:py-16">
        <div class="max-w-2xl">
            <h2 class="text-3xl font-extrabold text-white sm:text-4xl">{{ $title ?? 'Cracked screen? Battery draining fast?' }}</h2>
            <p class="mt-3 text-lg text-sky-200">{{ $text ?? 'Book in a couple of minutes. We collect your iPhone, repair it and bring it back to you.' }}</p>
        </div>
        <div class="flex flex-col gap-3 sm:flex-row">
            <x-ui.button :href="$bookingUrl" variant="light" size="lg" :external="$bookingOpensWhatsApp">
                <x-heroicon-s-wrench-screwdriver class="size-5 text-royal-600" aria-hidden="true" />
                Book a Repair
            </x-ui.button>
            <x-ui.button :href="$phoneHref" variant="outline-light" size="lg" data-track="click_to_call">
                <x-heroicon-s-phone class="size-5" aria-hidden="true" />
                {{ $phoneDisplay }}
            </x-ui.button>
            <x-ui.button :href="$whatsAppUrl" variant="whatsapp" size="lg" external data-track="click_whatsapp">
                <x-icon.whatsapp class="size-5" />
                WhatsApp
            </x-ui.button>
        </div>
    </div>
</section>
