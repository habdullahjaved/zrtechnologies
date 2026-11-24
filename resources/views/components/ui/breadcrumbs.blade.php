{{--
    Breadcrumb trail. Pass items as [['label' => 'iPhone Repair', 'url' => '...'], ['label' => 'Battery']].
    The last item is the current page and is not linked. "Home" is added automatically.
--}}
@props([
    'items' => [],
    'tone' => 'light',
])

<nav aria-label="Breadcrumb" {{ $attributes }}>
    <ol @class([
        'flex flex-wrap items-center gap-1.5 text-sm',
        'text-ink' => $tone === 'light',
        'text-sky-200' => $tone === 'dark',
    ])>
        <li>
            <a href="{{ route('home') }}" @class(['font-medium hover:underline', 'text-royal-600' => $tone === 'light', 'text-white' => $tone === 'dark'])>Home</a>
        </li>
        @foreach ($items as $item)
            <li class="flex items-center gap-1.5">
                <x-heroicon-m-chevron-right class="size-4 opacity-60" aria-hidden="true" />
                @if (! $loop->last && filled($item['url'] ?? null))
                    <a href="{{ $item['url'] }}" @class(['font-medium hover:underline', 'text-royal-600' => $tone === 'light', 'text-white' => $tone === 'dark'])>{{ $item['label'] }}</a>
                @else
                    <span aria-current="page">{{ $item['label'] }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
