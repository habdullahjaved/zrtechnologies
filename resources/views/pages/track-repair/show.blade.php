<x-layouts.app :seo="$seo">
    <x-site.page-header :title="'Repair '.$job->reference" :breadcrumbs="[['label' => 'Track a repair', 'url' => route('track-repair')], ['label' => $job->reference]]">
        {{ collect([$job->deviceModel?->name, $job->device_colour])->filter()->implode(', ') ?: 'Your iPhone' }}
        @if ($job->items->isNotEmpty())
            · {{ $job->items->pluck('description')->implode(', ') }}
        @endif
    </x-site.page-header>

    <x-ui.section tone="ice" spacing="tight">
        <div class="mx-auto grid max-w-4xl gap-6">
            <x-ui.card>
                <p class="text-sm font-semibold tracking-wider text-royal-600 uppercase">Current status</p>
                <h2 class="mt-1 text-2xl font-bold sm:text-3xl">{{ $job->status->customerLabel() }}</h2>
                <p class="mt-2 text-lg leading-relaxed">{{ $job->status->customerDescription() }}</p>

                @unless ($isStopped)
                    <ol class="mt-8 grid gap-4 sm:grid-cols-5 sm:gap-2" aria-label="Repair progress">
                        @foreach ($steps as $index => $stepStatus)
                            @php
                                $done = $index < $currentStep;
                                $current = $index === $currentStep;
                            @endphp
                            <li class="flex items-center gap-3 sm:flex-col sm:items-start sm:gap-2" @if ($current) aria-current="step" @endif>
                                <span @class([
                                    'flex size-9 shrink-0 items-center justify-center rounded-full text-sm font-bold',
                                    'bg-success-600 text-white' => $done,
                                    'bg-royal-600 text-white ring-4 ring-sky-200' => $current,
                                    'bg-ice-100 text-ink' => ! $done && ! $current,
                                ])>
                                    @if ($done)
                                        <x-heroicon-s-check class="size-5" aria-hidden="true" />
                                        <span class="sr-only">Done:</span>
                                    @else
                                        {{ $index + 1 }}
                                    @endif
                                </span>
                                <span @class(['text-sm', 'font-bold text-navy-900' => $current, 'font-semibold text-navy-900' => $done])>{{ $stepStatus->customerLabel() }}</span>
                            </li>
                        @endforeach
                    </ol>
                @endunless

                <dl class="mt-8 grid gap-4 border-t border-ice-100 pt-6 text-sm sm:grid-cols-3">
                    @if ($job->collectionSlotLabel())
                        <div><dt class="font-semibold text-navy-900">Collection</dt><dd class="mt-0.5">{{ $job->collectionSlotLabel() }}</dd></div>
                    @endif
                    <div><dt class="font-semibold text-navy-900">Estimated total</dt><dd class="mt-0.5">{{ $job->final_price_pence !== null ? \App\Support\Money::format($job->final_price_pence) : $job->estimatedTotalLabel() }}</dd></div>
                    @if ($job->warranty_expires_at)
                        <div><dt class="font-semibold text-navy-900">Warranty until</dt><dd class="mt-0.5">{{ $job->warranty_expires_at->format('j F Y') }}</dd></div>
                    @endif
                </dl>
            </x-ui.card>

            @if ($job->statusHistory->isNotEmpty())
                <x-ui.card>
                    <h2 class="text-xl font-bold">Updates from the team</h2>
                    <ul class="mt-4 grid gap-4">
                        @foreach ($job->statusHistory as $update)
                            <li class="rounded-xl bg-ice-50 p-4">
                                <p class="text-sm font-semibold text-navy-900">
                                    {{ $update->to_status->customerLabel() }}
                                    <span class="font-normal text-ink">· <time datetime="{{ $update->created_at->toIso8601String() }}">{{ $update->created_at->format('j F, g:ia') }}</time></span>
                                </p>
                                <p class="mt-1 whitespace-pre-line">{{ $update->customer_message }}</p>
                            </li>
                        @endforeach
                    </ul>
                </x-ui.card>
            @endif

            <div class="flex flex-wrap gap-3">
                <x-ui.button :href="$whatsAppUrl" variant="whatsapp" :external="true">
                    <x-icon.whatsapp class="size-4" />
                    Ask about this repair
                </x-ui.button>
                <x-ui.button :href="route('track-repair')" variant="secondary">Track another repair</x-ui.button>
            </div>
        </div>
    </x-ui.section>
</x-layouts.app>
