{{--
    Cookie consent banner (UK GDPR / PECR). "Accept all" and "Reject all" are
    equally prominent; "Manage preferences" lets visitors choose per category.
    It opens when no valid choice is stored, or from "Cookie settings" in the footer.
--}}
@inject('consent', \App\Settings\ConsentSettings::class)

<div
    x-data="cookieBanner"
    x-show="open"
    x-cloak
    x-transition:enter="transition duration-300 ease-out"
    x-transition:enter-start="translate-y-4 opacity-0"
    x-transition:enter-end="translate-y-0 opacity-100"
    @open-cookie-settings.window="openPreferences()"
    role="dialog"
    aria-modal="false"
    aria-labelledby="cookie-banner-title"
    aria-describedby="cookie-banner-text"
    @class(['fixed inset-x-0 bottom-0 z-50 p-3 sm:p-5 lg:pb-5', 'pb-[calc(env(safe-area-inset-bottom)+5rem)]' => ! request()->routeIs('booking.create'), 'pb-[calc(env(safe-area-inset-bottom)+0.75rem)]' => request()->routeIs('booking.create')])
>
    <div class="mx-auto max-w-3xl rounded-card border border-ice-100 bg-white p-4 shadow-lift sm:p-6">
        <h2 id="cookie-banner-title" class="text-base font-bold sm:text-lg">{{ $consent->banner_title }}</h2>
        <p id="cookie-banner-text" class="mt-1.5 text-sm leading-relaxed sm:mt-2 sm:text-[0.95rem]" :class="showPreferences ? '' : 'line-clamp-3 sm:line-clamp-none'">
            {{ $consent->banner_text }}
            @if (Route::has('legal.cookies'))
                <a href="{{ route('legal.cookies') }}" class="font-semibold text-royal-600 underline">Read our cookie policy</a>.
            @endif
        </p>

        <div x-show="showPreferences" x-cloak class="mt-4 space-y-3">
            <div class="flex items-start gap-3 rounded-xl bg-ice-50 p-3">
                <input type="checkbox" checked disabled class="mt-1 size-4 rounded border-slate-300 text-royal-600" id="consent-necessary">
                <label for="consent-necessary" class="text-sm">
                    <span class="block font-semibold text-navy-900">Essential (always on)</span>
                    Keep the booking and enquiry forms working and secure.
                </label>
            </div>
            <div class="flex items-start gap-3 rounded-xl bg-ice-50 p-3">
                <input type="checkbox" x-model="analytics" class="mt-1 size-4 rounded border-slate-300 text-royal-600 focus:ring-sky-400" id="consent-analytics">
                <label for="consent-analytics" class="text-sm">
                    <span class="block font-semibold text-navy-900">Analytics</span>
                    {{ $consent->analytics_description }}
                </label>
            </div>
            <div class="flex items-start gap-3 rounded-xl bg-ice-50 p-3">
                <input type="checkbox" x-model="marketing" class="mt-1 size-4 rounded border-slate-300 text-royal-600 focus:ring-sky-400" id="consent-marketing">
                <label for="consent-marketing" class="text-sm">
                    <span class="block font-semibold text-navy-900">Marketing</span>
                    {{ $consent->marketing_description }}
                </label>
            </div>
        </div>

        <div class="mt-4 grid grid-cols-2 gap-2 sm:mt-5 sm:grid-cols-3 sm:gap-2.5">
            <button type="button" @click="acceptAll()" class="rounded-xl bg-royal-600 px-3 py-2.5 font-semibold text-white hover:bg-blue-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-400 sm:px-4 sm:py-3">Accept all</button>
            <button type="button" @click="rejectAll()" class="rounded-xl bg-royal-600 px-3 py-2.5 font-semibold text-white hover:bg-blue-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-400 sm:px-4 sm:py-3">Reject all</button>
            <button type="button" x-show="! showPreferences" @click="showPreferences = true" class="col-span-2 rounded-xl px-4 py-1.5 text-sm font-semibold text-royal-600 underline hover:bg-ice-50 sm:col-span-1 sm:border sm:border-ice-100 sm:py-3 sm:text-base sm:text-navy-900 sm:no-underline">Manage preferences</button>
            <button type="button" x-show="showPreferences" x-cloak @click="saveChoices()" class="col-span-2 rounded-xl border border-ice-100 bg-white px-4 py-2.5 font-semibold text-navy-900 hover:bg-ice-50 sm:col-span-1 sm:py-3">Save my choices</button>
        </div>
    </div>
</div>
