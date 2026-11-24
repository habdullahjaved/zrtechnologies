@php
    $business = app(\App\Settings\BusinessSettings::class);
@endphp
<x-mail::layout>
{{-- Header --}}
<x-slot:header>
<x-mail::header :url="config('app.url')">
{{ $business->trading_name }}
</x-mail::header>
</x-slot:header>

{{-- Body --}}
{!! $slot !!}

{{-- Subcopy --}}
@isset($subcopy)
<x-slot:subcopy>
<x-mail::subcopy>
{!! $subcopy !!}
</x-mail::subcopy>
</x-slot:subcopy>
@endisset

{{-- Footer --}}
<x-slot:footer>
<x-mail::footer>
{{ $business->trading_name }} · {{ \App\Support\Phone::display($business->phone) }} · {{ $business->email }}

{{ $business->legal_name }}. Registered in {{ $business->registered_in }}, company number {{ $business->company_number }}. Registered office: {{ $business->registeredOfficeOneLine() }}.
</x-mail::footer>
</x-slot:footer>
</x-mail::layout>
