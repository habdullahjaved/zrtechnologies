@php
    $onDark = $variant === 'hero';
    $inputId = $idPrefix.'-postcode';
    $hasError = $errors->has('postcode');
@endphp

<div id="{{ $idPrefix }}">
    <form wire:submit="check" novalidate>
        <label for="{{ $inputId }}" @class(['mb-2 block text-sm font-semibold', 'text-white' => $onDark, 'text-navy-900' => ! $onDark])>
            Check we collect from you
        </label>
        <div class="flex flex-col gap-2.5 sm:flex-row">
            <input
                id="{{ $inputId }}"
                type="text"
                wire:model="postcode"
                autocomplete="postal-code"
                autocapitalize="characters"
                spellcheck="false"
                maxlength="10"
                placeholder="Your postcode, e.g. BB2 4NY"
                @if ($hasError) aria-invalid="true" aria-describedby="{{ $inputId }}-error" @endif
                @class([
                    'block w-full rounded-xl border bg-white px-4 py-3 text-base text-navy-950 uppercase shadow-sm placeholder:normal-case placeholder:text-slate-500 focus:border-royal-600 focus:ring-2 focus:ring-sky-400/40 sm:max-w-xs',
                    'border-slate-300' => ! $hasError,
                    'border-danger-600' => $hasError,
                ])
            >
            <x-ui.button type="submit" size="lg" wire:loading.attr="disabled" wire:target="check">
                <span wire:loading.remove wire:target="check" class="inline-flex items-center gap-2">
                    <x-heroicon-s-map-pin class="size-5" aria-hidden="true" />
                    Check postcode
                </span>
                <span wire:loading wire:target="check">Checking…</span>
            </x-ui.button>
        </div>

        @error('postcode')
            <p id="{{ $inputId }}-error" @class(['mt-2 flex items-center gap-1.5 text-sm font-semibold', 'text-white' => $onDark, 'text-danger-600' => ! $onDark])>
                <x-heroicon-m-exclamation-circle @class(['size-4 shrink-0', 'text-amber-300' => $onDark]) aria-hidden="true" />
                {{ $message }}
            </p>
        @enderror
    </form>

    <div aria-live="polite">
        @if ($result)
            <div class="mt-4 rounded-2xl bg-white p-5 text-left shadow-lift ring-1 ring-ice-100">
                @if ($result['status'] === 'covered')
                    <div class="flex gap-3">
                        <x-heroicon-s-check-circle class="size-7 shrink-0 text-success-600" aria-hidden="true" />
                        <div class="min-w-0">
                            <p class="text-lg font-bold text-navy-900">Good news, we collect from {{ $result['postcode'] }}.</p>
                            <dl class="mt-2 grid gap-1 text-sm text-ink">
                                <div class="flex gap-1.5"><dt class="font-semibold text-navy-900">Area:</dt><dd>{{ $result['area'] }}</dd></div>
                                <div class="flex gap-1.5"><dt class="font-semibold text-navy-900">Collection &amp; return:</dt><dd>{{ $result['fee'] }}</dd></div>
                                @if ($result['next_slot'])
                                    <div class="flex gap-1.5"><dt class="font-semibold text-navy-900">Next collection:</dt><dd>{{ $result['next_slot'] }}</dd></div>
                                @endif
                            </dl>
                            <div class="mt-4 flex flex-wrap gap-2.5">
                                <x-ui.button :href="$result['booking_url']" :external="$bookingOpensWhatsApp">
                                    <x-heroicon-s-wrench-screwdriver class="size-4" aria-hidden="true" />
                                    Book a collection
                                </x-ui.button>
                                <x-ui.button variant="ghost" wire:click="resetChecker">Check another postcode</x-ui.button>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="flex gap-3">
                        <x-heroicon-s-information-circle class="size-7 shrink-0 text-royal-600" aria-hidden="true" />
                        <div class="min-w-0">
                            <p class="text-lg font-bold text-navy-900">Sorry, we don’t collect from {{ $result['postcode'] }} yet.</p>
                            <p class="mt-1.5 text-sm">
                                @if ($postInEnabled)
                                    You can still use our post-in repair service: send us your iPhone and we will repair it and post it back.
                                @else
                                    We are growing our collection area. Message us and we will see what we can do.
                                @endif
                            </p>
                            <div class="mt-4 flex flex-wrap gap-2.5">
                                <x-ui.button :href="$result['booking_url']" variant="whatsapp" :external="true">
                                    <x-icon.whatsapp class="size-4" />
                                    Message us
                                </x-ui.button>
                                <x-ui.button variant="ghost" wire:click="resetChecker">Check another postcode</x-ui.button>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>
