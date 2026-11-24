@php
    $header = \App\Support\Content\Blocks::cells($data['header'] ?? '');
    $rows = array_map(fn ($row) => \App\Support\Content\Blocks::cells(is_array($row) ? ($row['cells'] ?? '') : ''), $data['rows'] ?? []);
@endphp

@if ($rows !== [])
    <div class="not-prose my-8 overflow-x-auto rounded-card border border-ice-100">
        <table class="w-full min-w-md text-left text-[0.95rem]">
            @if (filled($data['caption'] ?? null))
                <caption class="bg-ice-50 px-4 py-3 text-left font-heading font-bold text-navy-900">{{ $data['caption'] }}</caption>
            @endif
            @if (array_filter($header) !== [])
                <thead class="bg-navy-900 text-white">
                    <tr>
                        @foreach ($header as $cell)
                            <th scope="col" class="px-4 py-3 font-semibold">{{ $cell }}</th>
                        @endforeach
                    </tr>
                </thead>
            @endif
            <tbody class="divide-y divide-ice-100 bg-white">
                @foreach ($rows as $cells)
                    <tr>
                        @foreach ($cells as $cell)
                            @if ($loop->first)
                                <th scope="row" class="px-4 py-3 font-semibold text-navy-900">{{ $cell }}</th>
                            @else
                                <td class="px-4 py-3">{{ $cell }}</td>
                            @endif
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
