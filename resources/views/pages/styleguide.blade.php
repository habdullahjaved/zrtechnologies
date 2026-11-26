<x-layouts.app :seo="$seo">
    <x-ui.section tone="ice" spacing="tight">
        <x-ui.breadcrumbs :items="[['label' => 'Style guide']]" />
        <h1 class="mt-4 text-4xl font-extrabold">Design system</h1>
        <p class="mt-3 max-w-2xl text-lg">Every building block of the ZR Technologies website. This page only exists in local development.</p>
    </x-ui.section>

    <x-ui.section>
        <h2 class="text-2xl font-extrabold">Colour</h2>
        <ul class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
            @foreach ([
                ['bg-navy-950', '#00163F', 'navy-950', 'Footer, darkest text'],
                ['bg-navy-900', '#0A1F4E', 'navy-900', 'Headings, dark surfaces'],
                ['bg-royal-600', '#0058B0', 'royal-600', 'Buttons, links'],
                ['bg-blue-500', '#1E6FD9', 'blue-500', 'Hover, gradients'],
                ['bg-sky-400', '#3FA9F5', 'sky-400', 'Accent on dark only'],
                ['bg-ice-50 ring-1 ring-ice-100', '#EEF5FF', 'ice-50', 'Section backgrounds'],
                ['bg-ink', '#475569', 'ink', 'Body text'],
                ['bg-success-700', '#15803D', 'success-700', 'Success text'],
                ['bg-warning-700', '#B45309', 'warning-700', 'Warning text'],
                ['bg-danger-600', '#DC2626', 'danger-600', 'Errors'],
                ['bg-whatsapp', '#0F8044', 'whatsapp', 'WhatsApp buttons'],
                ['bg-brand-gradient', 'Gradient', 'brand-gradient', 'Hero, CTA bands'],
            ] as [$class, $hex, $token, $use])
                <li>
                    <div class="h-20 rounded-xl {{ $class }}"></div>
                    <p class="mt-2 text-sm font-semibold text-navy-900">{{ $token }}</p>
                    <p class="text-xs">{{ $hex }} · {{ $use }}</p>
                </li>
            @endforeach
        </ul>
    </x-ui.section>

    <x-ui.section tone="ice">
        <h2 class="text-2xl font-extrabold">Typography</h2>
        <div class="mt-6 space-y-4">
            <p class="font-heading text-5xl font-extrabold text-navy-900">Montserrat ExtraBold, headings</p>
            <p class="font-heading text-3xl font-bold text-navy-900">Montserrat Bold, sub-headings</p>
            <p class="max-w-2xl text-lg leading-relaxed">Inter, body text. We collect your iPhone, repair it with quality parts and bring it back to you, with a warranty on every repair.</p>
        </div>
    </x-ui.section>

    <x-ui.section>
        <h2 class="text-2xl font-extrabold">Buttons</h2>
        <div class="mt-6 flex flex-wrap items-center gap-3">
            <x-ui.button href="#">Primary</x-ui.button>
            <x-ui.button href="#" variant="secondary">Secondary</x-ui.button>
            <x-ui.button href="#" variant="ghost">Ghost</x-ui.button>
            <x-ui.button href="#" variant="whatsapp"><x-icon.whatsapp class="size-5" /> WhatsApp</x-ui.button>
            <x-ui.button size="sm">Small</x-ui.button>
            <x-ui.button size="lg">Large</x-ui.button>
            <x-ui.button disabled>Disabled</x-ui.button>
        </div>
        <div class="mt-6 flex flex-wrap gap-3 rounded-card bg-navy-900 p-6">
            <x-ui.button href="#" variant="light">Light (on dark)</x-ui.button>
            <x-ui.button href="#" variant="outline-light">Outline (on dark)</x-ui.button>
        </div>

        <h2 class="mt-14 text-2xl font-extrabold">Badges</h2>
        <div class="mt-6 flex flex-wrap gap-3">
            <x-ui.badge>Brand</x-ui.badge>
            <x-ui.badge tone="success">Completed</x-ui.badge>
            <x-ui.badge tone="warning">Awaiting parts</x-ui.badge>
            <x-ui.badge tone="danger">Cancelled</x-ui.badge>
            <x-ui.badge tone="neutral">Draft</x-ui.badge>
        </div>

        <h2 class="mt-14 text-2xl font-extrabold">Alerts</h2>
        <div class="mt-6 grid gap-4 md:grid-cols-2">
            <x-ui.alert title="Good to know">We never ask for your passcode in a form.</x-ui.alert>
            <x-ui.alert tone="success" title="Booking confirmed">Your reference is ZR-2026-000123.</x-ui.alert>
            <x-ui.alert tone="warning" title="Back up first">Please back up your iPhone before collection.</x-ui.alert>
            <x-ui.alert tone="danger" title="Something went wrong">Please check the highlighted fields.</x-ui.alert>
        </div>
    </x-ui.section>

    <x-ui.section tone="ice">
        <h2 class="text-2xl font-extrabold">Cards</h2>
        <div class="mt-6 grid gap-6 md:grid-cols-3">
            @foreach (['screen' => 'Screen replacement', 'battery' => 'Battery replacement', 'charging' => 'Charging port repair'] as $icon => $title)
                <x-ui.card :interactive="true">
                    <img src="{{ asset("images/icons/icon-{$icon}-navy-96.webp") }}" srcset="{{ asset("images/icons/icon-{$icon}-navy-192.webp") }} 2x" width="48" height="48" alt="" class="size-12" loading="lazy">
                    <h3 class="mt-4 text-xl font-bold">{{ $title }}</h3>
                    <p class="mt-2">Example card using a supplied repair icon.</p>
                </x-ui.card>
            @endforeach
        </div>
    </x-ui.section>

    <x-ui.section>
        <h2 class="text-2xl font-extrabold">Forms</h2>
        <form class="mt-6 grid max-w-2xl gap-5" onsubmit="return false">
            <x-form.input name="name" label="Full name" autocomplete="name" required />
            <x-form.input name="email" type="email" label="Email address" autocomplete="email" help="We'll send your booking confirmation here." required />
            <x-form.input name="company" label="Company" optional />
            <x-form.select name="model" label="iPhone model" placeholder="Choose your model" :options="['iphone-15' => 'iPhone 15', 'iphone-15-pro' => 'iPhone 15 Pro']" required />
            <x-form.textarea name="message" label="What's wrong with it?" optional />
            <x-form.checkbox name="marketing">Send me occasional offers and iPhone care tips. You can unsubscribe at any time.</x-form.checkbox>
            <x-ui.button type="submit" class="justify-self-start">Submit</x-ui.button>
        </form>
    </x-ui.section>

    <x-site.contact-actions title="Call-to-action band" text="Shown at the end of key pages." />
</x-layouts.app>
