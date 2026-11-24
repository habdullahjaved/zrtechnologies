@if ($data['level'] === 3)
    <h3 id="{{ $data['id'] }}" class="scroll-mt-28">{{ $data['text'] }}</h3>
@else
    <h2 id="{{ $data['id'] }}" class="scroll-mt-28">{{ $data['text'] }}</h2>
@endif
