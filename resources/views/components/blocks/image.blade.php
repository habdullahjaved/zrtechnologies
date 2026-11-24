@php($src = \App\Support\PublicFile::url($data['path'] ?? null))

@if ($src)
    <figure class="not-prose my-8">
        <img src="{{ $src }}" alt="{{ $data['alt'] ?? '' }}" loading="lazy" decoding="async" class="w-full rounded-card border border-ice-100 bg-ice-50 object-cover">
        @if (filled($data['caption'] ?? null))
            <figcaption class="mt-2.5 text-center text-sm">{{ $data['caption'] }}</figcaption>
        @endif
    </figure>
@endif
