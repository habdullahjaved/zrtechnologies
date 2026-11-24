@props([
    'seo' => new \App\Support\Seo\SeoData(),
])

<!DOCTYPE html>
<html lang="en-GB" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="format-detection" content="telephone=no">

    <x-site.tracking-head />
    <x-site.seo-head :seo="$seo" />

    <link rel="preload" href="{{ asset('fonts/montserrat-latin-800-normal.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="{{ asset('fonts/inter-latin-variable.woff2') }}" as="font" type="font/woff2" crossorigin>

    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="48x48">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <meta name="theme-color" content="#0A1F4E">
    <meta name="msapplication-config" content="{{ asset('browserconfig.xml') }}">

    {{ $head ?? '' }}

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="flex min-h-dvh flex-col">
    <a href="#main" class="sr-only z-60 rounded-lg bg-royal-600 px-4 py-3 font-semibold text-white focus:not-sr-only focus:fixed focus:top-3 focus:left-3">
        Skip to main content
    </a>

    <x-site.header />

    <main id="main" class="flex-1" tabindex="-1">
        {{ $slot }}
    </main>

    <x-site.footer />
    {{-- The booking form has its own fixed Continue bar on phones. --}}
    @unless (request()->routeIs('booking.create'))
        <x-site.contact-actions variant="mobile-bar" />
    @endunless
    <x-site.cookie-banner />

    @livewireScriptConfig
</body>
</html>
