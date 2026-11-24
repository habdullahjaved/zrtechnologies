{{-- Checkbox with a rich label (slot), e.g. consent statements. Never pre-ticked for marketing consent. --}}
@props([
    'name',
    'id' => null,
    'required' => false,
])

@php
    $id ??= str_replace(['.', '[', ']'], ['-', '-', ''], $name);
    $errorKey = str_replace(['[', ']'], ['.', ''], $name);
    $hasError = $errors->has($errorKey);
@endphp

<div {{ $attributes->only('class') }}>
    <div class="flex items-start gap-3">
        <input
            id="{{ $id }}"
            name="{{ $name }}"
            type="checkbox"
            value="1"
            @if ($required) required aria-required="true" @endif
            @if ($hasError) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
            {{ $attributes->except('class')->class('mt-0.5 size-5 shrink-0 rounded border-slate-400 text-royal-600 focus:ring-2 focus:ring-sky-400/40') }}
        >
        <label for="{{ $id }}" class="text-sm leading-relaxed text-ink-strong">{{ $slot }}</label>
    </div>
    @error($errorKey)
        <p id="{{ $id }}-error" class="mt-1.5 ml-8 text-sm font-medium text-danger-600">{{ $message }}</p>
    @enderror
</div>
