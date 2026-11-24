{{-- An email built from an editable message template. $body is Markdown with values already escaped. --}}
<x-mail::message>
{!! $body !!}

@if ($actionUrl)
<x-mail::button :url="$actionUrl">
{{ $actionText }}
</x-mail::button>
@endif

Thanks,<br>
The {{ $businessName }} team
</x-mail::message>
