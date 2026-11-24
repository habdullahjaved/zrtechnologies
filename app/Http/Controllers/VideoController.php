<?php

namespace App\Http\Controllers;

use App\Enums\SystemPage;
use App\Enums\VideoSource;
use App\Models\Page;
use App\Models\Video;
use App\Services\Seo\SchemaGraph;
use App\Services\Seo\SeoResolver;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;

/**
 * /videos (gallery, including "coming soon" placeholders) and /videos/{slug}
 * for real videos, with VideoObject structured data.
 */
class VideoController extends Controller
{
    public function __construct(
        private readonly SeoResolver $seo,
        private readonly SchemaGraph $schema,
    ) {}

    public function index(): View
    {
        $page = Page::system(SystemPage::Videos);
        $breadcrumbs = [['label' => 'Videos']];

        return view('pages.videos.index', [
            'seo' => $this->seo->forPage($page, $breadcrumbs, pageType: 'CollectionPage'),
            'page' => $page,
            'breadcrumbs' => $breadcrumbs,
            'videos' => Video::query()->listed()->with('service')->get(),
        ]);
    }

    public function show(string $slug): View
    {
        $video = Video::query()->playable()->where('slug', $slug)->with(['seoMeta', 'service'])->firstOrFail();
        $canonical = route('videos.show', $video->slug);
        $breadcrumbs = [
            ['label' => 'Videos', 'url' => route('videos.index')],
            ['label' => $video->title],
        ];

        $seo = $this->seo->make(
            meta: $video->seoMeta,
            title: $video->title,
            description: $video->description ?? Str::limit((string) $video->transcript, 155),
            image: $video->posterUrl() ?? 'images/og/og-videos.jpg',
            imageAlt: $video->title,
            canonical: $canonical,
            breadcrumbs: $breadcrumbs,
            schema: [$this->videoNode($video, $canonical)],
            type: 'video.other',
        );

        return view('pages.videos.show', [
            'seo' => $seo,
            'video' => $video,
            'breadcrumbs' => $breadcrumbs,
            'moreVideos' => Video::query()->listed()->whereKeyNot($video->getKey())->limit(4)->get(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function videoNode(Video $video, string $canonical): array
    {
        $node = [
            '@type' => 'VideoObject',
            '@id' => $canonical.'#video',
            'name' => $video->title,
            'description' => $video->description ?? $video->title,
            'thumbnailUrl' => $video->posterUrl() ?? asset('images/og/og-videos.jpg'),
            'uploadDate' => ($video->published_at ?? $video->created_at)?->toIso8601String(),
            'publisher' => ['@id' => $this->schema->businessId()],
            'inLanguage' => 'en-GB',
        ];

        if ($video->isoDuration() !== null) {
            $node['duration'] = $video->isoDuration();
        }

        if ($video->source === VideoSource::Upload && $video->fileUrl() !== null) {
            $node['contentUrl'] = $video->fileUrl();
        } elseif ($video->embedUrl() !== null) {
            $node['embedUrl'] = str_replace('?autoplay=1&rel=0&playsinline=1', '', $video->embedUrl());
        } elseif (filled($video->external_url)) {
            $node['url'] = $video->external_url;
        }

        if (filled($video->transcript)) {
            $node['transcript'] = $video->transcript;
        }

        return $node;
    }
}
