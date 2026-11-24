{{--
    Labelled text input with help text and validation error.
    The `name` is used for the id, the error bag key and (unless overridden) wire:model.
--}}
@props([
    'name',
    'label',
    'type' => 'text',
    'id' => null,
    'help' => null,
    'required' => false,
    'optional' => false,
])

@php
    $id ??= str_replace(['.', '[', ']'], ['-', '-', ''], $name);
    $errorKey = str_replace(['[', ']'], ['.', ''], $name);
    $hasError = $errors->has($errorKey);
    $describedBy = trim(($help ? "{$id}-help " : '').($hasError ? "{$id}-error" : ''));
@endphp

<div {{ $attributes->only('class') }}>
    <label for="{{ $id }}" class="mb-1.5 block text-sm font-semibold text-navy-900">
        {{ $label }}
        @if ($required)
            <span class="text-danger-600" aria-hidden="true">*</span>
        @elseif ($optional)
            <span class="font-normal text-ink">(optional)</span>
        @endif
    </label>
    <input
        id="{{ $id }}"
        name="{{ $name }}"
        type="{{ $type }}"
        @if ($required) required aria-required="true" @endif
        @if ($hasError) aria-invalid="true" @endif
        @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
        {{ $attributes->except('class')->class([
            'block w-full rounded-xl border bg-white px-4 py-3 text-base text-navy-950 shadow-sm placeholder:text-slate-400 focus:border-royal-600 focus:ring-2 focus:ring-sky-400/40',
            'border-slate-300' => ! $hasError,
            'border-danger-600' => $hasError,
        ]) }}
    >
    @if ($help)
        <p id="{{ $id }}-help" class="mt-1.5 text-sm text-ink">{{ $help }}</p>
    @endif
    @error($errorKey)
        <p id="{{ $id }}-error" class="mt-1.5 flex items-center gap-1 text-sm font-medium text-danger-600">
            <x-heroicon-m-exclamation-circle class="size-4 shrink-0" aria-hidden="true" />
            {{ $message }}
        </p>
    @enderror
</div>
