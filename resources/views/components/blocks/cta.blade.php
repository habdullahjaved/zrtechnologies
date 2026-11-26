@php
    $model = filled($data['device_model_id'] ?? null) ? \App\Models\DeviceModel::query()->find($data['device_model_id']) : null;
    $repair = filled($data['repair_type_id'] ?? null) ? \App\Models\RepairType::query()->find($data['repair_type_id']) : null;
    $url = app(\App\Support\BookingCallToAction::class)->url($model, $repair);
@endphp

<aside class="not-prose relative my-10 overflow-hidden rounded-card bg-brand-gradient p-6 text-white sm:p-8">
    <x-ui.swoosh class="absolute -right-10 -bottom-10 h-40 w-72 text-white/20" />
    <div class="relative">
        <p class="font-heading text-xl font-extrabold sm:text-2xl">{{ filled($data['title'] ?? null) ? $data['title'] : 'Need it fixed? We collect, repair and return.' }}</p>
        @if (filled($data['text'] ?? null))
            <p class="mt-2 max-w-xl leading-relaxed text-sky-100">{{ $data['text'] }}</p>
        @endif
        <x-ui.button :href="$url" variant="light" size="lg" class="mt-5">
            Book this repair
            <x-heroicon-m-arrow-right class="size-5" aria-hidden="true" />
        </x-ui.button>
    </div>
</aside>
