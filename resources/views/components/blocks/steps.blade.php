@php($items = array_values(array_filter($data['items'] ?? [], fn ($item) => is_array($item) && filled($item['title'] ?? null))))

@if ($items !== [])
    <ol class="not-prose my-8 grid gap-4">
        @foreach ($items as $item)
            <li class="flex gap-4 rounded-card border border-ice-100 bg-white p-5 shadow-soft">
                <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-royal-600 font-heading font-extrabold text-white" aria-hidden="true">{{ $loop->iteration }}</span>
                <div>
                    <p class="font-heading font-bold text-navy-900">{{ $item['title'] }}</p>
                    @if (filled($item['text'] ?? null))
                        <p class="mt-1 leading-relaxed">{{ $item['text'] }}</p>
                    @endif
                </div>
            </li>
        @endforeach
    </ol>
@endif
