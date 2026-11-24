{{--
    Responsive, art-directed image. Pass a WebP and a JPEG fallback; optionally a
    separate mobile pair shown below the `mobile-breakpoint`.
    Set :priority="true" for the largest above-the-fold image (LCP): it loads eagerly
    with fetchpriority="high". Everything else lazy-loads.
--}}
@props([
    'webp',
    'fallback',
    'alt' => '',
    'width',
    'height',
    'mobileWebp' => null,
    'mobileFallback' => null,
    'mobileBreakpoint' => '(max-width: 767px)',
    'priority' => false,
    'imgClass' => '',
])

<picture {{ $attributes }}>
    @if ($mobileWebp)
        <source media="{{ $mobileBreakpoint }}" srcset="{{ asset($mobileWebp) }}" type="image/webp">
    @endif
    @if ($mobileFallback)
        <source media="{{ $mobileBreakpoint }}" srcset="{{ asset($mobileFallback) }}" type="image/jpeg">
    @endif
    <source srcset="{{ asset($webp) }}" type="image/webp">
    <img
        src="{{ asset($fallback) }}"
        alt="{{ $alt }}"
        width="{{ $width }}"
        height="{{ $height }}"
        decoding="async"
        @if ($priority) fetchpriority="high" loading="eager" @else loading="lazy" @endif
        class="{{ $imgClass }}"
    >
</picture>
