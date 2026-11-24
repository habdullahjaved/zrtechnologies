@php
    $ids = array_map('intval', (array) ($data['faq_ids'] ?? []));
    $faqs = $ids === [] ? collect() : \App\Models\Faq::query()->listed()->whereKey($ids)->get()->sortBy(fn ($faq) => array_search($faq->id, $ids, true))->values();
@endphp

@if ($faqs->isNotEmpty())
    <div class="not-prose my-10">
        @if (filled($data['title'] ?? null))
            <h2 class="mb-5 text-2xl font-extrabold">{{ $data['title'] }}</h2>
        @endif
        <x-site.faq-list :faqs="$faqs" />
    </div>
@endif
