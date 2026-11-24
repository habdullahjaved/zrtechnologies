{{-- Plain share links: no third-party scripts or tracking. --}}
@props([
    'url',
    'title',
])

@php
    $encodedUrl = rawurlencode($url);
    $encodedTitle = rawurlencode($title);
    $links = [
        ['label' => 'WhatsApp', 'href' => "https://wa.me/?text={$encodedTitle}%20{$encodedUrl}"],
        ['label' => 'Facebook', 'href' => "https://www.facebook.com/sharer/sharer.php?u={$encodedUrl}"],
        ['label' => 'X', 'href' => "https://x.com/intent/post?url={$encodedUrl}&text={$encodedTitle}"],
        ['label' => 'Email', 'href' => "mailto:?subject={$encodedTitle}&body={$encodedUrl}"],
    ];
@endphp

<div {{ $attributes->class('flex flex-wrap items-center gap-2') }}>
    <span class="mr-1 text-sm font-semibold text-navy-900">Share:</span>
    @foreach ($links as $link)
        <a href="{{ $link['href'] }}" @if (! str_starts_with($link['href'], 'mailto:')) target="_blank" rel="noopener" @endif class="rounded-full border border-ice-100 bg-white px-3.5 py-1.5 text-sm font-semibold text-navy-900 hover:border-sky-200 hover:bg-ice-50">
            {{ $link['label'] }}<span class="sr-only"> (opens in a new tab)</span>
        </a>
    @endforeach
    <button type="button" x-data="copyLink(@js($url))" @click="copy()" class="rounded-full border border-ice-100 bg-white px-3.5 py-1.5 text-sm font-semibold text-navy-900 hover:border-sky-200 hover:bg-ice-50">
        <span x-text="copied ? 'Link copied' : 'Copy link'">Copy link</span>
    </button>
</div>
