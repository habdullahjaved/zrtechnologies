<form wire:submit="track" class="grid gap-4 sm:grid-cols-[1fr_1fr_auto] sm:items-start" novalidate>
    <x-form.input
        name="reference"
        id="track-reference"
        label="Booking reference"
        wire:model="reference"
        placeholder="ZR-2026-000123"
        autocapitalize="characters"
        spellcheck="false"
        maxlength="24"
        required
    />
    <x-form.input
        name="postcode"
        id="track-postcode"
        label="Collection postcode"
        wire:model="postcode"
        placeholder="BB2 4NY"
        autocomplete="postal-code"
        autocapitalize="characters"
        maxlength="10"
        required
    />
    <x-ui.button type="submit" size="lg" class="sm:mt-7" wire:loading.attr="disabled" wire:target="track">
        <span wire:loading.remove wire:target="track">Track repair</span>
        <span wire:loading wire:target="track">Finding…</span>
    </x-ui.button>
</form>
