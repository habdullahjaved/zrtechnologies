@php
    $tone = $data['tone'] ?? 'tip';
    $styles = [
        'tip' => ['wrapper' => 'border-sky-200 bg-ice-50', 'icon' => 'heroicon-s-light-bulb', 'iconClass' => 'text-royal-600', 'label' => 'Tip'],
        'info' => ['wrapper' => 'border-ice-100 bg-white', 'icon' => 'heroicon-s-information-circle', 'iconClass' => 'text-royal-600', 'label' => 'Good to know'],
        'warning' => ['wrapper' => 'border-amber-200 bg-amber-50', 'icon' => 'heroicon-s-exclamation-triangle', 'iconClass' => 'text-warning-700', 'label' => 'Important'],
    ];
    $style = $styles[$tone] ?? $styles['tip'];
@endphp

<aside class="not-prose my-8 flex gap-4 rounded-card border p-5 sm:p-6 {{ $style['wrapper'] }}" @if ($tone === 'warning') role="note" @endif>
    <x-dynamic-component :component="$style['icon']" class="mt-0.5 size-6 shrink-0 {{ $style['iconClass'] }}" aria-hidden="true" />
    <div>
        <p class="font-heading font-bold text-navy-900">{{ filled($data['title'] ?? null) ? $data['title'] : $style['label'] }}</p>
        @if (filled($data['text'] ?? null))
            <p class="mt-1.5 leading-relaxed">{!! nl2br(e($data['text'])) !!}</p>
        @endif
    </div>
</aside>
