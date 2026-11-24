<?php

namespace Database\Seeders;

use App\Enums\MenuLocation;
use App\Enums\PublishStatus;
use App\Enums\SystemPage;
use App\Enums\VideoSource;
use App\Models\Author;
use App\Models\Faq;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\RepairType;
use App\Models\Service;
use App\Models\ServiceArea;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Models\Video;
use App\Services\Cache\PageCache;
use App\Services\Seo\ContentQuality;
use App\Settings\BusinessSettings;
use App\Support\Content\SystemPageDefaults;
use App\Support\Navigation\SiteNavigation;
use Database\Seeders\Content\AreaContent;
use Database\Seeders\Content\BlogContent;
use Database\Seeders\Content\FaqContent;
use Database\Seeders\Content\PageContent;
use Database\Seeders\Content\ServicePageContent;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * Website content: system pages (with legal drafts), the ten repair pages,
 * FAQs, blog categories, the owner's author profile, six draft cornerstone
 * posts, video placeholders, menus and local content for the location pages.
 *
 * Safe to run repeatedly and in production: records are only created when
 * missing (and area content only filled in when empty), so the owner's edits
 * are never overwritten.
 */
class ContentSeeder extends Seeder
{
    /**
     * Planned shorts (brief §11), seeded as "coming soon" placeholders.
     *
     * @var list<array{title: string, label: string, service: string|null}>
     */
    private const array VIDEOS = [
        ['title' => 'How our collection and delivery repair works', 'label' => 'How collection and delivery works', 'service' => null],
        ['title' => 'iPhone screen replacement in 60 seconds', 'label' => 'Screen replacement in 60 seconds', 'service' => 'screen-replacement'],
        ['title' => 'iPhone battery replacement', 'label' => 'Battery replacement', 'service' => 'battery-replacement'],
        ['title' => 'Charging port: clean or repair?', 'label' => 'Charging port clean vs repair', 'service' => 'charging-port-repair'],
        ['title' => 'Meet the technician', 'label' => 'Meet the technician', 'service' => null],
        ['title' => 'How to protect your new iPhone', 'label' => 'Protect your new iPhone', 'service' => 'screen-replacement'],
    ];

    /** @var array<string, int> FaqContent key => FAQ id */
    private array $faqIds = [];

    public function run(BusinessSettings $business): void
    {
        $this->seedFaqs();
        $this->seedSystemPages($business);
        $this->seedServices();
        $this->seedBlog($business);
        $this->seedVideos();
        $this->seedMenus();
        $this->seedAreaContent();

        // Model events are off while seeding, so run the checks the saving hooks would.
        app(ContentQuality::class)->refreshServiceAreas();
        app(PageCache::class)->flush();
    }

    private function seedFaqs(): void
    {
        $sort = 10;

        foreach (FaqContent::all() as $key => [$topic, $question, $answer]) {
            $this->faqIds[$key] = Faq::query()->firstOrCreate(['question' => $question], [
                'topic' => $topic,
                'answer' => $answer,
                'sort' => $sort += 10,
            ])->getKey();
        }
    }

    private function seedSystemPages(BusinessSettings $business): void
    {
        $blocks = PageContent::all($business);
        $faqs = PageContent::faqs();

        foreach (SystemPage::cases() as $key) {
            $defaults = SystemPageDefaults::for($key);

            $page = Page::query()->firstOrNew(['key' => $key->value]);

            if ($page->exists) {
                continue;
            }

            $page->fill([
                'title' => $defaults['title'],
                'slug' => $key->slug(),
                'eyebrow' => $defaults['eyebrow'] ?? null,
                'intro' => $defaults['intro'] ?? null,
                'blocks' => $blocks[$key->value] ?? null,
                'status' => PublishStatus::Published,
                'published_at' => now(),
                'needs_legal_review' => $key->isLegal(),
                // The repair tracker only works with a reference; it has nothing to rank for.
                'show_in_sitemap' => $key !== SystemPage::TrackRepair,
            ]);
            $page->fillDerivedAttributes();
            $page->save();

            $meta = SystemPageDefaults::seoMeta($key);

            if (filled($meta->title) || filled($meta->description)) {
                $page->seoMeta()->save($meta);
            }

            $this->attachFaqs($page, $faqs[$key->value] ?? []);
        }
    }

    private function seedServices(): void
    {
        $category = ServiceCategory::query()->where('slug', ServiceCategory::IPHONE_REPAIR)->first();

        if ($category === null) {
            return;
        }

        $repairTypes = RepairType::query()->pluck('id', 'slug');

        foreach (ServicePageContent::all() as $index => $content) {
            $service = Service::query()->firstOrNew(['slug' => $content['slug']]);

            if ($service->exists) {
                continue;
            }

            $service->fill([
                'service_category_id' => $category->getKey(),
                'name' => $content['name'],
                'summary' => $content['summary'],
                'intro' => $content['intro'],
                'blocks' => $content['blocks'],
                'icon' => $content['icon'],
                'og_image' => $content['og_image'],
                'sort' => ($index + 1) * 10,
            ]);
            $service->fillDerivedAttributes();
            $service->save();

            foreach ($content['repairs'] as $sort => $slug) {
                if (isset($repairTypes[$slug])) {
                    $service->repairTypes()->attach($repairTypes[$slug], ['sort' => $sort]);
                }
            }

            $keys = $content['faqs'];

            foreach ($content['own_faqs'] as [$question, $answer]) {
                $faq = Faq::query()->firstOrCreate(['question' => $question], [
                    'topic' => ServicePageContent::faqTopic(),
                    'answer' => $answer,
                    // Specific to this repair, so it stays on its page rather than /faqs.
                    'show_on_faq_page' => false,
                    'sort' => 500 + $index,
                ]);
                $this->faqIds['service:'.$question] = $faq->getKey();
                $keys = ['service:'.$question, ...$keys];
            }

            $this->attachFaqs($service, $keys);
        }
    }

    private function seedBlog(BusinessSettings $business): void
    {
        foreach (BlogContent::categories() as $index => $category) {
            PostCategory::query()->firstOrCreate(['slug' => $category['slug']], [
                'name' => $category['name'],
                'description' => $category['description'],
                'sort' => ($index + 1) * 10,
            ]);
        }

        $author = Author::query()->firstOrCreate(['slug' => Str::slug($business->director_name)], [
            'user_id' => User::query()->where('email', config('zr.super_admin.email'))->value('id'),
            'name' => $business->director_name,
            'role_title' => 'Director, '.$business->trading_name,
            'bio' => "{$business->director_name} runs {$business->trading_name}, the Blackburn iPhone repair service that collects, repairs and returns phones across East Lancashire.",
        ]);

        $categories = PostCategory::query()->pluck('id', 'slug');
        $services = Service::query()->pluck('id', 'slug');

        foreach (BlogContent::posts() as $content) {
            $post = Post::query()->withTrashed()->firstOrNew(['slug' => $content['slug']]);

            if ($post->exists) {
                continue;
            }

            $post->fill([
                'post_category_id' => $categories[$content['category']],
                'author_id' => $author->getKey(),
                'service_id' => $content['service'] !== null ? ($services[$content['service']] ?? null) : null,
                'title' => $content['title'],
                'excerpt' => $content['excerpt'],
                'blocks' => $content['blocks'],
                'status' => PublishStatus::Draft,
            ]);
            $post->fillDerivedAttributes();
            $post->save();
        }
    }

    private function seedVideos(): void
    {
        $services = Service::query()->pluck('id', 'slug');

        foreach (self::VIDEOS as $index => $video) {
            Video::query()->withTrashed()->firstOrCreate(['slug' => Str::slug($video['title'])], [
                'title' => $video['title'],
                'source' => VideoSource::YouTube,
                'is_placeholder' => true,
                'placeholder_label' => $video['label'],
                'service_id' => $video['service'] !== null ? ($services[$video['service']] ?? null) : null,
                'sort' => ($index + 1) * 10,
            ]);
        }
    }

    /**
     * Seed the editable menus with the default links, once.
     */
    private function seedMenus(): void
    {
        if (MenuItem::query()->exists()) {
            return;
        }

        $menus = [
            MenuLocation::Header->value => SiteNavigation::DEFAULT_HEADER,
            MenuLocation::FooterCompany->value => SiteNavigation::DEFAULT_FOOTER_COMPANY,
            MenuLocation::FooterLegal->value => SiteNavigation::DEFAULT_FOOTER_LEGAL,
        ];

        foreach ($menus as $menu => $links) {
            foreach ($links as $index => $link) {
                if (! Route::has($link['route'])) {
                    continue;
                }

                MenuItem::query()->create([
                    'menu' => $menu,
                    'label' => $link['label'],
                    'url' => '/'.ltrim((string) parse_url(route($link['route']), PHP_URL_PATH), '/'),
                    'sort' => ($index + 1) * 10,
                ]);
            }
        }
    }

    /**
     * Local content for location pages that do not have any yet.
     */
    private function seedAreaContent(): void
    {
        foreach (AreaContent::all() as $slug => $content) {
            ServiceArea::query()
                ->where('slug', $slug)
                ->whereNull('intro')
                ->whereNull('landmarks')
                ->update([
                    'intro' => $content['intro'],
                    'landmarks' => $content['landmarks'],
                    'blocks' => json_encode($content['blocks'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ]);
        }

        // FAQs for location pages: how collection works where you live.
        $areaFaqs = ['where-collect', 'how-soon', 'need-to-be-home', 'collection-fee'];

        foreach (ServiceArea::query()->whereDoesntHave('faqs')->get() as $area) {
            $this->attachFaqs($area, $areaFaqs);
        }
    }

    /**
     * @param  list<string>  $keys
     */
    private function attachFaqs(Page|Service|ServiceArea $record, array $keys): void
    {
        $attach = [];

        foreach ($keys as $sort => $key) {
            if (isset($this->faqIds[$key])) {
                $attach[$this->faqIds[$key]] = ['sort' => $sort];
            }
        }

        if ($attach !== []) {
            $record->faqs()->syncWithoutDetaching($attach);
        }
    }
}
