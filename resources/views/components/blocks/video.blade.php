@php($video = filled($data['video_id'] ?? null) ? \App\Models\Video::query()->listed()->find($data['video_id']) : null)

@if ($video)
    <div class="not-prose mx-auto my-10 max-w-xs">
        <x-site.video-card :video="$video" :heading-level="3" />
    </div>
@endif
