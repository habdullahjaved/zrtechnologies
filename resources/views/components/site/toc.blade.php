@props([
    'headings',
])

@if (count($headings) >= 2)
    <nav {{ $attributes->class('rounded-card border border-ice-100 bg-ice-50 p-5') }} aria-labelledby="toc-heading">
        <p id="toc-heading" class="font-heading text-sm font-bold tracking-wider text-navy-900 uppercase">On this page</p>
        <ol class="mt-3 space-y-2 text-[0.95rem]">
            @foreach ($headings as $heading)
                <li><a href="#{{ $heading['id'] }}" class="text-royal-600 hover:underline">{{ $heading['text'] }}</a></li>
            @endforeach
        </ol>
    </nav>
@endif
