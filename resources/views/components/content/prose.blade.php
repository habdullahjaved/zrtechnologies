{{-- Rendered content blocks in the site's typography. --}}
@props([
    'content',
])

@unless ($content->isEmpty())
    <div {{ $attributes->class('prose prose-slate max-w-none prose-headings:font-heading prose-headings:font-extrabold prose-headings:text-navy-900 prose-h2:text-2xl sm:prose-h2:text-3xl prose-a:font-semibold prose-a:text-royal-600 prose-strong:text-navy-900 prose-li:marker:text-royal-600 text-ink') }}>
        {{ $content->html }}
    </div>
@endunless
