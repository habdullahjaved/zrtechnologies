{!! '<'.'?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1" xmlns:video="http://www.google.com/schemas/sitemap-video/1.1">
@foreach ($entries as $entry)
    <url>
        <loc>{{ $entry['loc'] }}</loc>
@if (! empty($entry['lastmod']))
        <lastmod>{{ $entry['lastmod'] }}</lastmod>
@endif
@foreach ($entry['images'] ?? [] as $image)
        <image:image>
            <image:loc>{{ $image['loc'] }}</image:loc>
        </image:image>
@endforeach
@if (isset($entry['video']))
        <video:video>
            <video:thumbnail_loc>{{ $entry['video']['thumbnail'] }}</video:thumbnail_loc>
            <video:title>{{ $entry['video']['title'] }}</video:title>
            <video:description>{{ \Illuminate\Support\Str::limit($entry['video']['description'], 2000) }}</video:description>
@if (! empty($entry['video']['content_loc']))
            <video:content_loc>{{ $entry['video']['content_loc'] }}</video:content_loc>
@endif
@if (! empty($entry['video']['player_loc']))
            <video:player_loc>{{ $entry['video']['player_loc'] }}</video:player_loc>
@endif
@if (! empty($entry['video']['duration']))
            <video:duration>{{ $entry['video']['duration'] }}</video:duration>
@endif
@if (! empty($entry['video']['publication_date']))
            <video:publication_date>{{ $entry['video']['publication_date'] }}</video:publication_date>
@endif
        </video:video>
@endif
    </url>
@endforeach
</urlset>
