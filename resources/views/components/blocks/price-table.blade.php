@php
    $model = filled($data['device_model_id'] ?? null) ? \App\Models\DeviceModel::query()->find($data['device_model_id']) : null;
    $repair = filled($data['repair_type_id'] ?? null) ? \App\Models\RepairType::query()->find($data['repair_type_id']) : null;
@endphp

<section class="rounded-card border border-ice-100 bg-ice-50 p-6 sm:p-8">
    <h2 class="text-xl font-bold text-navy-900">{{ filled($data['title'] ?? null) ? $data['title'] : 'Ask us about repair options' }}</h2>
    <p class="mt-2 leading-relaxed">Choose your iPhone model and service, then select a collection time that works for you.</p>
    <a href="{{ route('booking.create', array_filter(['model' => $model?->slug, 'repair' => $repair?->slug])) }}" class="mt-5 inline-flex items-center gap-2 rounded-lg bg-royal-600 px-5 py-3 font-semibold text-white hover:bg-blue-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-royal-600">
        Book this repair
        <x-heroicon-m-arrow-right class="size-4" aria-hidden="true" />
    </a>
</section>
