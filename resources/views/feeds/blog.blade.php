{!! '<'.'?xml version="1.0" encoding="UTF-8"?>' !!}
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:dc="http://purl.org/dc/elements/1.1/">
    <channel>
        <title>{{ $page->title }} | {{ config('app.name') }}</title>
        <link>{{ route('blog.index') }}</link>
        <atom:link href="{{ route('blog.feed') }}" rel="self" type="application/rss+xml" />
        <description>{{ $page->intro }}</description>
        <language>en-gb</language>
        <lastBuildDate>{{ $updatedAt->toRfc2822String() }}</lastBuildDate>
@foreach ($posts as $post)
        <item>
            <title>{{ $post->title }}</title>
            <link>{{ $post->url() }}</link>
            <guid isPermaLink="true">{{ $post->url() }}</guid>
            <pubDate>{{ $post->published_at?->toRfc2822String() }}</pubDate>
@if ($post->author)
            <dc:creator>{{ $post->author->name }}</dc:creator>
@endif
            <category>{{ $post->category->name }}</category>
            <description>{{ $post->excerpt }}</description>
        </item>
@endforeach
    </channel>
</rss>
