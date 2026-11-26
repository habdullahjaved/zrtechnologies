<x-layouts.app :seo="$seo">
    <x-site.page-header :title="$page->title" :eyebrow="$page->eyebrow" :breadcrumbs="$breadcrumbs">
        {{ $page->intro }}
    </x-site.page-header>

    <x-ui.section tone="white">
        <ol class="relative mx-auto grid max-w-4xl gap-8 before:absolute before:top-6 before:bottom-6 before:left-6 before:w-0.5 before:bg-ice-100 sm:before:left-7">
            @foreach ([
                [
                    'icon' => 'heroicon-o-calendar-days',
                    'title' => 'Book online',
                    'time' => 'About 2 minutes',
                    'text' => 'Enter your postcode, choose your iPhone model and repair, then pick a collection window that suits you.',
                ],
                [
                    'icon' => 'heroicon-o-truck',
                    'title' => 'We collect your iPhone',
                    'time' => 'From '.$leadTimeHours.' hours after booking',
                    'text' => 'A member of our team collects your iPhone from your home or workplace in the window you chose. Please back it up first and remove any case. We check the phone with you and note its condition.',
                ],
                [
                    'icon' => 'heroicon-o-magnifying-glass',
                    'title' => 'Diagnosis',
                    'time' => 'Usually the same day',
                    'text' => 'We test every function before we start, so nothing is missed. If we find anything unexpected, we stop and discuss it with you before continuing.',
                ],
                [
                    'icon' => 'heroicon-o-wrench-screwdriver',
                    'title' => 'Repair and quality check',
                    'time' => 'Most repairs in under 2 hours',
                    'text' => 'We repair your iPhone with quality-tested parts, then check the screen, touch, cameras, speakers, charging, Wi-Fi and Face ID or Touch ID before it leaves us.',
                ],
                [
                    'icon' => 'heroicon-o-hand-thumb-up',
                    'title' => 'We bring it back',
                    'time' => 'Often the same or next working day',
                    'text' => 'We return your iPhone to your door at a time that suits you. The repair is covered by our warranty.',
                ],
            ] as $step)
                <li class="relative flex gap-5 sm:gap-7">
                    <span class="relative z-10 flex size-12 shrink-0 items-center justify-center rounded-2xl bg-royal-600 text-white shadow-soft sm:size-14">
                        <x-dynamic-component :component="$step['icon']" class="size-6 sm:size-7" aria-hidden="true" />
                    </span>
                    <div class="flex-1 rounded-card border border-ice-100 bg-white p-6 shadow-soft">
                        <p class="font-heading text-sm font-extrabold tracking-wider text-royal-600">STEP {{ $loop->iteration }}</p>
                        <h2 class="mt-1 text-xl font-bold sm:text-2xl">{{ $step['title'] }}</h2>
                        <p class="mt-1 inline-flex items-center gap-1.5 text-sm font-semibold text-navy-900">
                            <x-heroicon-s-clock class="size-4 text-royal-600" aria-hidden="true" />
                            {{ $step['time'] }}
                        </p>
                        <p class="mt-3 leading-relaxed">{{ $step['text'] }}</p>
                    </div>
                </li>
            @endforeach
        </ol>

        <p class="mx-auto mt-10 max-w-4xl text-center text-sm">Timings are typical. Water damage, data recovery and repairs that need a part ordering can take longer; we always tell you what to expect.</p>
    </x-ui.section>

    <x-ui.section tone="ice">
        <div class="mx-auto grid max-w-5xl gap-6 md:grid-cols-2">
            <x-ui.card>
                <x-heroicon-o-currency-pound class="size-8 text-royal-600" aria-hidden="true" />
                <h2 class="mt-3 text-xl font-bold">Paying for your repair</h2>
                <p class="mt-2 leading-relaxed">{{ $paymentNote }}</p>
            </x-ui.card>
            <x-ui.card>
                <x-heroicon-o-lock-closed class="size-8 text-royal-600" aria-hidden="true" />
                <h2 class="mt-3 text-xl font-bold">Your data and passcode</h2>
                <p class="mt-2 leading-relaxed">{{ $passcodeNotice }}</p>
            </x-ui.card>
        </div>
    </x-ui.section>

    @unless ($content->isEmpty())
        <x-ui.section tone="white" width="narrow">
            <x-content.prose :content="$content" />
        </x-ui.section>
    @endunless

    @if ($faqs->isNotEmpty())
        <x-ui.section tone="white" width="narrow">
            <x-ui.section-heading eyebrow="FAQs" title="Questions about collection and return" />
            <x-site.faq-list :faqs="$faqs" class="mt-10" />
        </x-ui.section>
    @endif

    <x-site.contact-actions />
</x-layouts.app>
