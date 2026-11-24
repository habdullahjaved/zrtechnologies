<div id="{{ $idPrefix }}">
    @if ($reference)
        <div class="flex gap-3" role="status">
            <x-heroicon-s-check-circle class="size-8 shrink-0 text-success-600" aria-hidden="true" />
            <div>
                <p class="text-lg font-bold text-navy-900">
                    @switch ($type)
                        @case('callback') Thanks, we will call you back. @break
                        @case('quote') Thanks, we will send your quote shortly. @break
                        @default Thanks, we have your message.
                    @endswitch
                </p>
                <p class="mt-1">Your reference is <strong>{{ $reference }}</strong>. We usually reply within a few hours, day or night.</p>
                <button type="button" wire:click="sendAnother" class="mt-3 text-sm font-semibold text-royal-600 hover:underline">Send another message</button>
            </div>
        </div>
    @else
        <form wire:submit="submit" class="grid gap-4 sm:grid-cols-2" novalidate>
            <x-form.honeypot />

            @error('form')
                <x-ui.alert tone="warning" class="sm:col-span-2">{{ $message }}</x-ui.alert>
            @enderror

            @if ($type === 'business')
                <x-form.input name="companyName" :id="$idPrefix.'-company'" label="Organisation" wire:model="companyName" autocomplete="organization" class="sm:col-span-2" required />
            @endif

            <x-form.input name="name" :id="$idPrefix.'-name'" label="Your name" wire:model="name" autocomplete="name" @class(['sm:col-span-2' => $type !== 'callback']) required />

            @if ($type === 'callback')
                <x-form.input name="phone" :id="$idPrefix.'-phone'" label="Phone number" type="tel" wire:model="phone" autocomplete="tel" inputmode="tel" required />
                <x-form.select name="callbackTime" :id="$idPrefix.'-time'" label="Best time to call" :options="$callbackTimes" wire:model="callbackTime" class="sm:col-span-2" />
            @else
                <x-form.input name="email" :id="$idPrefix.'-email'" label="Email" type="email" wire:model="email" autocomplete="email" :required="$type !== 'quote'" />
                <x-form.input name="phone" :id="$idPrefix.'-phone'" label="Phone number" type="tel" wire:model="phone" autocomplete="tel" inputmode="tel" :optional="$type !== 'quote'" />
            @endif

            @if ($type === 'quote')
                <x-form.select
                    name="model"
                    :id="$idPrefix.'-model'"
                    label="iPhone model"
                    :options="$this->models->pluck('name', 'slug')->all()"
                    placeholder="Choose your model…"
                    wire:model="model"
                    required
                />
                <x-form.select
                    name="repair"
                    :id="$idPrefix.'-repair'"
                    label="Repair"
                    :options="$this->repairTypes->pluck('name', 'slug')->all()"
                    placeholder="Not sure"
                    wire:model="repair"
                />
                <x-form.input name="postcode" :id="$idPrefix.'-postcode'" label="Postcode" wire:model="postcode" autocomplete="postal-code" autocapitalize="characters" maxlength="10" optional />
            @endif

            @if ($type === 'business')
                <x-form.select name="fleetSize" :id="$idPrefix.'-fleet'" label="How many devices?" :options="$fleetSizes" placeholder="Choose…" wire:model="fleetSize" />
            @endif

            <x-form.textarea
                name="message"
                :id="$idPrefix.'-message'"
                :label="match ($type) { 'quote' => 'What’s wrong with it?', 'callback' => 'What would you like to talk about?', 'business' => 'What do you need?', default => 'Your message' }"
                wire:model="message"
                rows="4"
                class="sm:col-span-2"
                :required="in_array($type, ['contact', 'business'], true)"
                :optional="in_array($type, ['quote', 'callback'], true)"
            />

            @if ($type !== 'callback')
                <x-form.checkbox name="marketingConsent" :id="$idPrefix.'-marketing'" wire:model="marketingConsent" class="sm:col-span-2">
                    Send me occasional tips and offers by email. You can unsubscribe at any time.
                </x-form.checkbox>
            @endif

            <div class="sm:col-span-2">
                <x-ui.button type="submit" size="lg" wire:loading.attr="disabled" wire:target="submit">
                    <span wire:loading.remove wire:target="submit">{{ $submitLabel }}</span>
                    <span wire:loading wire:target="submit">Sending…</span>
                </x-ui.button>
            </div>
        </form>
    @endif
</div>
