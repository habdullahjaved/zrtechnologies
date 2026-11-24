@php
    $model = $this->selectedModel;
    $lines = $this->summaryLines;
@endphp

<div id="booking" class="scroll-mt-28" x-data x-init="window.zrTrack?.('booking_step_' + $wire.step, { step: $wire.step }); $watch('$wire.step', (step) => window.zrTrack?.('booking_step_' + step, { step }))" x-on:booking-step.window="$el.scrollIntoView({ behavior: 'smooth', block: 'start' })">
    {{-- Progress --}}
    <nav aria-label="Booking progress" class="mb-6">
        <ol class="grid grid-cols-6 gap-1.5 sm:gap-2">
            @foreach ($stepLabels as $number => $label)
                @php
                    $isCurrent = $number === $step;
                    $isDone = $number < $step;
                    $canJump = $number < $step || ($number <= $furthestStep && $number !== $step && $serviceAreaId !== null);
                @endphp
                <li>
                    <button
                        type="button"
                        @if ($canJump) wire:click="goToStep({{ $number }})" @else disabled @endif
                        @if ($isCurrent) aria-current="step" @endif
                        class="group flex w-full flex-col items-start gap-1.5 text-left disabled:cursor-default"
                    >
                        <span @class([
                            'h-1.5 w-full rounded-full transition',
                            'bg-royal-600' => $isDone || $isCurrent,
                            'bg-ice-100' => ! $isDone && ! $isCurrent,
                        ])></span>
                        <span @class([
                            'hidden text-xs font-semibold sm:block',
                            'text-navy-900' => $isCurrent,
                            'text-royal-600 group-hover:underline' => $isDone,
                            'text-ink' => ! $isDone && ! $isCurrent,
                        ])>{{ $number }}. {{ $label }}</span>
                    </button>
                </li>
            @endforeach
        </ol>
        <p class="mt-2 text-sm font-semibold text-navy-900 sm:hidden">Step {{ $step }} of {{ count($stepLabels) }}: {{ $stepLabels[$step] }}</p>
    </nav>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_22rem] lg:items-start">
        <x-ui.card padding="p-5 sm:p-8">
            @error('form')
                <x-ui.alert tone="warning" class="mb-6">{{ $message }}</x-ui.alert>
            @enderror

            {{-- Step 1: postcode --}}
            @if ($step === 1)
                <h2 class="text-2xl font-bold">Where should we collect from?</h2>
                <p class="mt-2">Enter your postcode and we will check we collect from you.</p>

                <form wire:submit="checkPostcode" class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-start" novalidate>
                    <x-form.input
                        name="postcode"
                        label="Postcode"
                        wire:model="postcode"
                        autocomplete="postal-code"
                        autocapitalize="characters"
                        spellcheck="false"
                        maxlength="10"
                        placeholder="e.g. BB2 4NY"
                        class="sm:w-64"
                        required
                    />
                    <x-ui.button type="submit" size="lg" class="sm:mt-7" wire:loading.attr="disabled" wire:target="checkPostcode">
                        <span wire:loading.remove wire:target="checkPostcode">Check postcode</span>
                        <span wire:loading wire:target="checkPostcode">Checking…</span>
                    </x-ui.button>
                </form>

                @if ($outOfArea)
                    <div class="mt-8 rounded-2xl border border-ice-100 bg-ice-50 p-5 sm:p-6" aria-live="polite">
                        @if ($outOfAreaReference)
                            <div class="flex gap-3">
                                <x-heroicon-s-check-circle class="size-7 shrink-0 text-success-600" aria-hidden="true" />
                                <div>
                                    <p class="text-lg font-bold text-navy-900">Thanks, we will be in touch.</p>
                                    <p class="mt-1">Your reference is <strong>{{ $outOfAreaReference }}</strong>. We usually reply within a few working hours.</p>
                                </div>
                            </div>
                        @else
                            <p class="text-lg font-bold text-navy-900">Sorry, we don’t collect from {{ $postcode }} yet.</p>
                            <p class="mt-1.5">
                                @if ($settings->post_in_enabled)
                                    You can still use our post-in repair service: send us your iPhone, we repair it and post it back. Leave your details and we will send you everything you need.
                                @else
                                    We are growing our collection area. Leave your details and we will see what we can do.
                                @endif
                            </p>

                            <form wire:submit="requestOutOfAreaHelp" class="mt-5 grid gap-4 sm:grid-cols-2" novalidate>
                                <x-form.honeypot />
                                <x-form.input name="outOfAreaName" label="Your name" wire:model="outOfAreaName" autocomplete="name" class="sm:col-span-2" required />
                                <x-form.input name="outOfAreaPhone" label="Mobile" type="tel" wire:model="outOfAreaPhone" autocomplete="tel" inputmode="tel" />
                                <x-form.input name="outOfAreaEmail" label="Email" type="email" wire:model="outOfAreaEmail" autocomplete="email" />
                                <x-form.textarea name="outOfAreaMessage" label="What’s wrong with your iPhone?" wire:model="outOfAreaMessage" rows="3" class="sm:col-span-2" optional />
                                <div class="flex flex-wrap gap-3 sm:col-span-2">
                                    <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="requestOutOfAreaHelp">
                                        {{ $settings->post_in_enabled ? 'Send me post-in details' : 'Ask us to get in touch' }}
                                    </x-ui.button>
                                    <x-ui.button variant="ghost" wire:click="changePostcode">Try another postcode</x-ui.button>
                                </div>
                            </form>
                        @endif
                    </div>
                @endif
            @endif

            {{-- Step 2: device (search, popular models, or the full list in a compact dropdown) --}}
            @if ($step === 2)
                @php
                    $allModels = $this->models;
                    $popularModels = $allModels->where('is_popular', true)->take(8);
                    $searchIndex = $allModels->map(fn ($deviceModel) => ['slug' => $deviceModel->slug, 'name' => $deviceModel->name])->values();
                @endphp

                <h2 class="text-2xl font-bold">Which iPhone do you have?</h2>
                <p class="mt-2">Not sure? Open Settings › General › About on the phone.</p>

                <div class="mt-6" x-data="{ search: '', models: {{ \Illuminate\Support\Js::from($searchIndex) }}, get matches() { const term = this.search.toLowerCase().replace(/iphone/g, '').trim(); return term === '' ? [] : this.models.filter(m => m.name.toLowerCase().includes(term)).slice(0, 8); } }">
                    <label for="booking-model-search" class="mb-1.5 block text-sm font-semibold text-navy-900">Search for your model</label>
                    <div class="relative">
                        <x-heroicon-o-magnifying-glass class="pointer-events-none absolute top-1/2 left-3.5 size-5 -translate-y-1/2 text-slate-400" aria-hidden="true" />
                        <input
                            id="booking-model-search"
                            type="search"
                            x-model="search"
                            autocomplete="off"
                            placeholder="e.g. 15 Pro Max"
                            class="block w-full rounded-xl border border-slate-300 bg-white py-3.5 pr-4 pl-11 text-base text-navy-950 shadow-sm placeholder:text-slate-400 focus:border-royal-600 focus:ring-2 focus:ring-sky-400/40"
                        >
                    </div>

                    {{-- Search results --}}
                    <ul x-show="search.trim() !== ''" x-cloak class="mt-3 grid gap-2 sm:grid-cols-2" aria-live="polite">
                        <template x-for="match in matches" :key="match.slug">
                            <li>
                                <button type="button" @click="$wire.chooseModel(match.slug)" class="flex w-full items-center gap-2.5 rounded-xl border border-ice-100 bg-white px-4 py-3.5 text-left font-semibold text-navy-900 hover:border-royal-600 hover:bg-ice-50 focus-visible:outline-2 focus-visible:outline-sky-400">
                                    <x-heroicon-o-device-phone-mobile class="size-5 shrink-0 text-royal-600" aria-hidden="true" />
                                    <span x-text="match.name"></span>
                                </button>
                            </li>
                        </template>
                        <li x-show="matches.length === 0" class="rounded-xl bg-ice-50 px-4 py-3 text-sm sm:col-span-2">No model matches. Try just the number, e.g. "15", or choose from the list below.</li>
                    </ul>

                    {{-- Popular models --}}
                    @if ($popularModels->isNotEmpty())
                        <div x-show="search.trim() === ''">
                            <p class="mt-6 text-sm font-semibold text-navy-900">Popular models</p>
                            <ul class="mt-2.5 flex flex-wrap gap-2">
                                @foreach ($popularModels as $deviceModel)
                                    <li wire:key="popular-{{ $deviceModel->id }}">
                                        <button
                                            type="button"
                                            wire:click="chooseModel('{{ $deviceModel->slug }}')"
                                            @class([
                                                'rounded-full border px-4 py-2.5 text-sm font-semibold transition focus-visible:outline-2 focus-visible:outline-sky-400',
                                                'border-royal-600 bg-royal-600 text-white' => $model?->id === $deviceModel->id,
                                                'border-ice-100 bg-white text-navy-900 hover:border-royal-600' => $model?->id !== $deviceModel->id,
                                            ])
                                        >{{ $deviceModel->name }}</button>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- Every model, in one compact dropdown --}}
                    <div class="mt-6">
                        <label for="booking-model-list" class="mb-1.5 block text-sm font-semibold text-navy-900">Or choose from all models</label>
                        <select
                            id="booking-model-list"
                            @change="if ($event.target.value) { $wire.chooseModel($event.target.value) }"
                            class="block w-full rounded-xl border border-slate-300 bg-white py-3.5 pr-10 pl-4 text-base text-navy-950 shadow-sm focus:border-royal-600 focus:ring-2 focus:ring-sky-400/40"
                        >
                            <option value="">Select your iPhone…</option>
                            @foreach ($allModels->groupBy(fn ($deviceModel) => $deviceModel->family ?? 'Other') as $family => $familyModels)
                                <optgroup label="{{ $family }}">
                                    @foreach ($familyModels as $deviceModel)
                                        <option value="{{ $deviceModel->slug }}" @selected($model?->id === $deviceModel->id)>{{ $deviceModel->name }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>

                    @error('model')
                        <p class="mt-3 text-sm font-medium text-danger-600">{{ $message }}</p>
                    @enderror

                    <div wire:loading.flex wire:target="chooseModel" class="mt-4 items-center gap-2 text-sm font-semibold text-royal-600">
                        <x-heroicon-o-arrow-path class="size-4 animate-spin" aria-hidden="true" />
                        Loading repairs…
                    </div>
                </div>

                <div class="sticky bottom-0 z-10 -mx-5 mt-8 flex justify-between gap-3 border-t border-ice-100 bg-white/95 px-5 py-3 backdrop-blur sm:static sm:mx-0 sm:border-0 sm:bg-transparent sm:p-0 sm:backdrop-blur-none">
                    <x-ui.button variant="ghost" wire:click="back">Back</x-ui.button>
                    @if ($model)
                        <x-ui.button size="lg" wire:click="submitDevice">Continue with {{ $model->name }}</x-ui.button>
                    @endif
                </div>
            @endif

            {{-- Step 3: repairs --}}
            @if ($step === 3 && $model)
                <h2 class="text-2xl font-bold">What needs fixing on your {{ $model->name }}?</h2>
                <p class="mt-2">Choose one or more repairs. Prices include parts and labour.</p>

                <form wire:submit="submitRepairs" class="mt-6" novalidate>
                    <fieldset>
                        <legend class="sr-only">Repairs</legend>
                        <ul class="grid gap-3">
                            @foreach ($this->prices as $repairId => $options)
                                @php
                                    $repair = $options->first()->repairType;
                                    $isChosen = in_array((string) $repairId, $selectedRepairs, true);
                                @endphp
                                @continue($repair->slug === \App\Models\RepairType::DIAGNOSTICS)
                                <li wire:key="repair-{{ $repairId }}" @class([
                                    'rounded-2xl border p-4 transition sm:p-5',
                                    'border-royal-600 bg-ice-50 ring-1 ring-royal-600' => $isChosen,
                                    'border-ice-100 bg-white' => ! $isChosen,
                                ])>
                                    <label class="flex cursor-pointer items-start gap-3">
                                        <input type="checkbox" value="{{ $repairId }}" wire:model.live="selectedRepairs" class="mt-1 size-5 shrink-0 rounded border-slate-400 text-royal-600 focus:ring-2 focus:ring-sky-400/40">
                                        <span class="min-w-0 flex-1">
                                            <span class="flex flex-wrap items-baseline justify-between gap-2">
                                                <span class="font-bold text-navy-900">{{ $repair->name }}</span>
                                                <span class="font-heading font-extrabold text-navy-900">
                                                    @if ($options->count() > 1 || $repair->requires_diagnosis)<span class="text-sm font-normal text-ink">from</span>@endif
                                                    {{ $options->min(fn ($price) => $price->effectivePricePence()) === 0 ? 'Free' : \App\Support\Money::format($options->min(fn ($price) => $price->effectivePricePence())) }}
                                                </span>
                                            </span>
                                            @if ($repair->summary)
                                                <span class="mt-1 block text-sm leading-relaxed">{{ $repair->summary }}</span>
                                            @endif
                                        </span>
                                    </label>

                                    @if ($isChosen && $options->count() > 1)
                                        <fieldset class="mt-4 sm:ml-8">
                                            <legend class="text-sm font-semibold text-navy-900">Part quality</legend>
                                            <div class="mt-2 grid gap-2 sm:grid-cols-2">
                                                @foreach ($options as $option)
                                                    @php($qualityValue = (string) ($option->part_quality_id ?? ''))
                                                    <label wire:key="quality-{{ $option->id }}" @class([
                                                        'flex cursor-pointer items-start gap-2.5 rounded-xl border bg-white px-3.5 py-3 text-sm',
                                                        'border-royal-600' => ($qualities[(string) $repairId] ?? null) === $qualityValue,
                                                        'border-ice-100' => ($qualities[(string) $repairId] ?? null) !== $qualityValue,
                                                    ])>
                                                        <input type="radio" value="{{ $qualityValue }}" wire:model.live="qualities.{{ $repairId }}" class="mt-0.5 size-4 border-slate-400 text-royal-600 focus:ring-2 focus:ring-sky-400/40">
                                                        <span class="min-w-0 flex-1">
                                                            <span class="flex justify-between gap-2 font-semibold text-navy-900">
                                                                <span>{{ $option->partQuality->name ?? 'Standard' }}</span>
                                                                <span>{{ $option->formattedPrice() }}</span>
                                                            </span>
                                                            @if ($option->partQuality?->description)
                                                                <span class="mt-0.5 block text-ink">{{ $option->partQuality->description }}</span>
                                                            @endif
                                                            @if ($warranty = $option->warrantyLabel())
                                                                <span class="mt-0.5 block text-ink">{{ $warranty }} warranty</span>
                                                            @endif
                                                        </span>
                                                    </label>
                                                @endforeach
                                            </div>
                                        </fieldset>
                                    @elseif ($isChosen && ($warranty = $options->first()->warrantyLabel()))
                                        <p class="mt-2 text-sm sm:ml-8">{{ $warranty }} warranty{{ $repair->requires_diagnosis ? ' · final price confirmed after diagnosis' : '' }}</p>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </fieldset>

                    @error('selectedRepairs')
                        <p class="mt-3 text-sm font-medium text-danger-600">{{ $message }}</p>
                    @enderror
                    @error('repairs')
                        <p class="mt-3 text-sm font-medium text-danger-600">{{ $message }}</p>
                    @enderror

                    @if ($diagnostics)
                        <div class="mt-5 flex flex-col gap-3 rounded-2xl border border-dashed border-sky-200 p-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="font-bold text-navy-900">Not sure what’s wrong?</p>
                                <p class="text-sm">We test every function and give you a fixed quote before any repair: {{ $diagnostics->effectivePricePence() === 0 ? 'free' : $diagnostics->formattedPrice() }}.</p>
                            </div>
                            <x-ui.button variant="secondary" wire:click="chooseDiagnostics">Book a diagnosis</x-ui.button>
                        </div>
                    @endif

                    <div class="sticky bottom-0 z-10 -mx-5 mt-8 flex flex-wrap justify-between gap-3 border-t border-ice-100 bg-white/95 px-5 py-3 backdrop-blur sm:static sm:mx-0 sm:border-0 sm:bg-transparent sm:p-0 sm:backdrop-blur-none">
                        <x-ui.button variant="ghost" wire:click="back">Back</x-ui.button>
                        <x-ui.button type="submit" size="lg">Continue</x-ui.button>
                    </div>
                </form>
            @endif

            {{-- Step 4: collection slot --}}
            @if ($step === 4)
                <h2 class="text-2xl font-bold">When should we collect?</h2>
                <p class="mt-2">Choose a time window. The driver will text you when they are on the way.</p>

                <form wire:submit="submitCollection" class="mt-6" novalidate>
                    @if ($slotsByDate->isEmpty())
                        <x-ui.alert tone="warning" title="No collection times are available online right now.">
                            Please call or message us and we will find a time that works for you.
                        </x-ui.alert>
                    @else
                        <fieldset>
                            <legend class="sr-only">Collection time</legend>
                            <div class="grid gap-5">
                                @foreach ($slotsByDate->take(10) as $date => $daySlots)
                                    <div wire:key="day-{{ $date }}">
                                        @php($day = $daySlots->first()->date)
                                        <p class="text-sm font-bold text-navy-900">
                                            {{ \Illuminate\Support\Str::before($daySlots->first()->label(), ",") }}
                                            @if ($day->isToday() || $day->isTomorrow())
                                                <span class="font-normal text-ink">· {{ $day->format("l j F") }}</span>
                                            @endif
                                        </p>
                                        <div class="mt-2 flex flex-wrap gap-2">
                                            @foreach ($daySlots as $collectionSlot)
                                                <label wire:key="slot-{{ $collectionSlot->key() }}" @class([
                                                    'cursor-pointer rounded-xl border px-4 py-2.5 text-sm font-semibold transition focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-sky-400',
                                                    'border-royal-600 bg-royal-600 text-white' => $slot === $collectionSlot->key(),
                                                    'border-ice-100 bg-white text-navy-900 hover:border-sky-200' => $slot !== $collectionSlot->key(),
                                                ])>
                                                    <input type="radio" value="{{ $collectionSlot->key() }}" wire:model.live="slot" class="sr-only">
                                                    {{ $collectionSlot->window->timeRange() }}
                                                    @if ($collectionSlot->remaining <= 2)
                                                        <span @class(['ml-1 text-xs font-normal', 'text-sky-100' => $slot === $collectionSlot->key(), 'text-warning-700' => $slot !== $collectionSlot->key()])>{{ $collectionSlot->remaining }} left</span>
                                                    @endif
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </fieldset>
                    @endif
                    @error('slot')
                        <p class="mt-3 text-sm font-medium text-danger-600">{{ $message }}</p>
                    @enderror

                    <fieldset class="mt-8">
                        <legend class="text-sm font-bold text-navy-900">Where should we return it?</legend>
                        <div class="mt-2 grid gap-2 sm:grid-cols-2">
                            @foreach (['same' => 'Same address as collection', 'different' => 'A different address'] as $value => $label)
                                <label class="flex cursor-pointer items-center gap-2.5 rounded-xl border border-ice-100 bg-white px-4 py-3 text-sm font-semibold text-navy-900">
                                    <input type="radio" value="{{ $value }}" wire:model.live="returnTo" class="size-4 border-slate-400 text-royal-600 focus:ring-2 focus:ring-sky-400/40">
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                    </fieldset>

                    @if ($returnTo === 'different')
                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                            <x-form.input name="returnLine1" label="Address line 1" wire:model="returnLine1" autocomplete="off" class="sm:col-span-2" required />
                            <x-form.input name="returnLine2" label="Address line 2" wire:model="returnLine2" autocomplete="off" class="sm:col-span-2" optional />
                            <x-form.input name="returnTown" label="Town" wire:model="returnTown" required />
                            <x-form.input name="returnPostcode" label="Postcode" wire:model="returnPostcode" autocapitalize="characters" maxlength="10" required />
                        </div>
                    @endif

                    <div class="sticky bottom-0 z-10 -mx-5 mt-8 flex flex-wrap justify-between gap-3 border-t border-ice-100 bg-white/95 px-5 py-3 backdrop-blur sm:static sm:mx-0 sm:border-0 sm:bg-transparent sm:p-0 sm:backdrop-blur-none">
                        <x-ui.button variant="ghost" wire:click="back">Back</x-ui.button>
                        <x-ui.button type="submit" size="lg">Continue</x-ui.button>
                    </div>
                </form>
            @endif

            {{-- Step 5: details --}}
            @if ($step === 5)
                <h2 class="text-2xl font-bold">Your details</h2>
                <p class="mt-2">So we can confirm your booking and the driver can find you.</p>

                <form wire:submit="submitDetails" class="mt-6 grid gap-4 sm:grid-cols-2" novalidate>
                    <x-form.input name="firstName" label="First name" wire:model="firstName" autocomplete="given-name" required />
                    <x-form.input name="lastName" label="Last name" wire:model="lastName" autocomplete="family-name" required />
                    <x-form.input name="phone" label="Mobile number" type="tel" wire:model.blur="phone" autocomplete="tel" inputmode="tel" help="The driver texts this number on the day." required />
                    <x-form.input name="email" label="Email" type="email" wire:model.blur="email" autocomplete="email" help="For your confirmation and repair updates." required />
                    <x-form.input name="line1" label="Address line 1" wire:model="line1" autocomplete="address-line1" class="sm:col-span-2" required />
                    <x-form.input name="line2" label="Address line 2" wire:model="line2" autocomplete="address-line2" class="sm:col-span-2" optional />
                    <x-form.input name="town" label="Town" wire:model="town" autocomplete="address-level2" required />
                    <div>
                        <p class="mb-1.5 block text-sm font-semibold text-navy-900">Postcode</p>
                        <p class="flex items-center justify-between rounded-xl border border-ice-100 bg-ice-50 px-4 py-3">
                            <span class="font-semibold text-navy-900">{{ $postcode }}</span>
                            <button type="button" wire:click="goToStep(1)" class="text-sm font-semibold text-royal-600 hover:underline">Change</button>
                        </p>
                    </div>
                    <details class="group rounded-xl border border-ice-100 px-4 py-3 sm:col-span-2" @if ($colour || $storage) open @endif>
                        <summary class="cursor-pointer text-sm font-semibold text-royal-600">Add your iPhone’s colour and storage (optional)</summary>
                        <div class="mt-3 grid gap-4 sm:grid-cols-2">
                            <x-form.input name="colour" label="Colour" wire:model="colour" maxlength="40" placeholder="e.g. Midnight" optional />
                            <x-form.input name="storage" label="Storage" wire:model="storage" maxlength="20" placeholder="e.g. 128GB" list="booking-storage-options" optional />
                            <datalist id="booking-storage-options">
                                @foreach (['64GB', '128GB', '256GB', '512GB', '1TB', '2TB'] as $size)
                                    <option value="{{ $size }}"></option>
                                @endforeach
                            </datalist>
                        </div>
                    </details>
                    <x-form.textarea name="notes" label="Anything we should know?" wire:model="notes" rows="3" class="sm:col-span-2" help="e.g. parking, a gate code, or more about the fault." optional />

                    <x-ui.alert tone="info" title="Your passcode and data" class="sm:col-span-2">
                        {{ $settings->passcode_notice }}
                    </x-ui.alert>

                    <div class="grid gap-4 sm:col-span-2">
                        <x-form.checkbox name="acceptTerms" wire:model="acceptTerms" required>
                            I have read and agree to the
                            @if (Route::has('legal.repair-terms'))
                                <a href="{{ route('legal.repair-terms') }}" target="_blank" rel="noopener" class="font-semibold text-royal-600 underline">repair terms</a>,
                            @else
                                repair terms,
                            @endif
                            including backing up my iPhone before collection.
                        </x-form.checkbox>
                        <x-form.checkbox name="startStraightAway" wire:model="startStraightAway">
                            Please start my repair straight away. I understand I can cancel free of charge until the repair starts, and that once it is complete my 14-day right to cancel ends. If you leave this unticked we will check with you before starting.
                        </x-form.checkbox>
                        <x-form.checkbox name="marketingConsent" wire:model="marketingConsent">
                            Send me occasional tips and offers by email. You can unsubscribe at any time.
                        </x-form.checkbox>
                    </div>

                    <p class="text-sm text-ink sm:col-span-2">
                        We keep what you type here so we can help if you get stuck. See our
                        @if (Route::has('legal.privacy'))
                            <a href="{{ route('legal.privacy') }}" class="font-semibold text-royal-600 underline">privacy policy</a>.
                        @else
                            privacy policy.
                        @endif
                    </p>

                    <div class="mt-2 sm:col-span-2 sticky bottom-0 z-10 -mx-5 flex flex-wrap items-center justify-between gap-3 border-t border-ice-100 bg-white/95 px-5 py-3 backdrop-blur sm:static sm:mx-0 sm:border-0 sm:bg-transparent sm:p-0 sm:backdrop-blur-none">
                        <x-ui.button variant="ghost" wire:click="back">Back</x-ui.button>
                        <x-ui.button type="submit" size="lg">Review booking</x-ui.button>
                    </div>
                </form>
            @endif

            {{-- Step 6: review --}}
            @if ($step === 6 && $model)
                <h2 class="text-2xl font-bold">Check and confirm</h2>
                <p class="mt-2">Please check everything is right. You can change any step.</p>

                <dl class="mt-6 divide-y divide-ice-100 rounded-2xl border border-ice-100">
                    @foreach ([
                        ['label' => 'Device', 'value' => collect([$model->name, $colour, $storage])->filter()->implode(', '), 'step' => $colour || $storage ? 5 : 2],
                        ['label' => 'Repairs', 'value' => collect($lines)->map(fn ($line) => $line['label'].($line['quality'] ? ' ('.$line['quality'].')' : '').': '.$line['price'])->implode("\n"), 'step' => 3],
                        ['label' => 'Collection', 'value' => $chosenSlot?->label() ?? '', 'step' => 4],
                        ['label' => 'Address', 'value' => collect([$line1, $line2, $town, $postcode])->filter()->implode(', '), 'step' => 5],
                        ['label' => 'Return to', 'value' => $returnTo === 'different' ? collect([$returnLine1, $returnLine2, $returnTown, $returnPostcode])->filter()->implode(', ') : 'Same address', 'step' => 4],
                        ['label' => 'Contact', 'value' => trim($firstName.' '.$lastName)."\n".$phone."\n".$email, 'step' => 5],
                    ] as $row)
                        <div class="grid gap-1 px-4 py-3.5 sm:grid-cols-[8rem_1fr_auto] sm:gap-4">
                            <dt class="text-sm font-semibold text-navy-900">{{ $row['label'] }}</dt>
                            <dd class="whitespace-pre-line text-sm">{{ $row['value'] }}</dd>
                            <dd><button type="button" wire:click="goToStep({{ $row['step'] }})" class="text-sm font-semibold text-royal-600 hover:underline">Change<span class="sr-only"> {{ mb_strtolower($row['label']) }}</span></button></dd>
                        </div>
                    @endforeach
                </dl>

                <div class="mt-6 rounded-2xl bg-ice-50 p-5">
                    <div class="flex justify-between text-sm"><span>Repairs</span><span class="font-semibold text-navy-900">{{ $repairsTotal }}</span></div>
                    <div class="mt-1.5 flex justify-between text-sm"><span>Collection &amp; return</span><span class="font-semibold text-navy-900">{{ $collectionFee }}</span></div>
                    <div class="mt-3 flex justify-between border-t border-ice-100 pt-3"><span class="font-bold text-navy-900">Estimated total</span><span class="font-heading text-xl font-extrabold text-navy-900">{{ $estimatedTotal }}</span></div>
                    <p class="mt-3 text-sm">{{ $settings->payment_note }}</p>
                </div>

                <form wire:submit="confirm" class="mt-8 sticky bottom-0 z-10 -mx-5 flex flex-wrap items-center justify-between gap-3 border-t border-ice-100 bg-white/95 px-5 py-3 backdrop-blur sm:static sm:mx-0 sm:border-0 sm:bg-transparent sm:p-0 sm:backdrop-blur-none" novalidate>
                    <x-form.honeypot />
                    <x-ui.button variant="ghost" wire:click="back">Back</x-ui.button>
                    <x-ui.button type="submit" size="lg" wire:loading.attr="disabled" wire:target="confirm">
                        <span wire:loading.remove wire:target="confirm" class="inline-flex items-center gap-2">
                            <x-heroicon-s-check class="size-5" aria-hidden="true" />
                            Confirm booking
                        </span>
                        <span wire:loading wire:target="confirm">Booking…</span>
                    </x-ui.button>
                </form>
                @error('confirm')
                    <p class="mt-3 text-sm font-medium text-danger-600">{{ $message }}</p>
                @enderror
            @endif
        </x-ui.card>

        {{-- Summary --}}
        <aside class="lg:sticky lg:top-28" aria-label="Your booking so far">
            <x-ui.card padding="p-5">
                <h2 class="text-lg font-bold">Your booking</h2>
                <dl class="mt-4 grid gap-3 text-sm">
                    <div>
                        <dt class="font-semibold text-navy-900">Postcode</dt>
                        <dd>{{ $serviceAreaId ? $postcode.' · '.$areaName : 'Not checked yet' }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-navy-900">Device</dt>
                        <dd>{{ $model?->name ?? 'Not chosen yet' }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-navy-900">Repairs</dt>
                        <dd>
                            @forelse ($lines as $line)
                                <div class="flex justify-between gap-2">
                                    <span>{{ $line['label'].($line['quality'] ? ' ('.$line['quality'].')' : '') }}</span>
                                    <span class="font-semibold text-navy-900">{{ $line['price'] }}</span>
                                </div>
                            @empty
                                Not chosen yet
                            @endforelse
                        </dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-navy-900">Collection</dt>
                        <dd>{{ $chosenSlot?->label() ?? 'Not chosen yet' }}</dd>
                    </div>
                    @if ($serviceAreaId)
                        <div class="flex justify-between">
                            <dt class="font-semibold text-navy-900">Collection &amp; return</dt>
                            <dd>{{ $collectionFee }}</dd>
                        </div>
                    @endif
                </dl>
                @if ($lines !== [])
                    <div class="mt-4 flex items-baseline justify-between border-t border-ice-100 pt-4">
                        <span class="font-bold text-navy-900">Estimated total</span>
                        <span class="font-heading text-xl font-extrabold text-navy-900">{{ $estimatedTotal }}</span>
                    </div>
                @endif
            </x-ui.card>

            <ul class="mt-4 grid gap-2 px-1 text-sm">
                @foreach (["We never ask for your passcode", "Pay when your iPhone comes back", "We ask before any extra cost"] as $promise)
                    <li class="flex items-center gap-2"><x-heroicon-s-check-circle class="size-5 shrink-0 text-success-600" aria-hidden="true" />{{ $promise }}</li>
                @endforeach
            </ul>
        </aside>
    </div>
</div>
