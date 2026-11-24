<?php

namespace App\Providers;

use App\Models\Author;
use App\Models\DeviceModel;
use App\Models\Faq;
use App\Models\MenuItem;
use App\Models\ModelRepairPage;
use App\Models\Page;
use App\Models\PartQuality;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\RepairPrice;
use App\Models\RepairType;
use App\Models\Review;
use App\Models\SeoMeta;
use App\Models\Service;
use App\Models\ServiceArea;
use App\Models\ServiceCategory;
use App\Models\Tag;
use App\Models\Video;
use App\Policies\ActivityPolicy;
use App\Policies\RolePolicy;
use App\Services\Cache\PageCache;
use App\Services\Content\ServiceDirectory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Spatie\Activitylog\Models\Activity;
use Spatie\LaravelSettings\Events\SettingsSaved;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Saving or deleting any of these changes what public pages show, so the
     * full-page cache is cleared.
     *
     * @var list<class-string<Model>>
     */
    private const array PUBLIC_CONTENT_MODELS = [
        Page::class, Service::class, Post::class, PostCategory::class, Author::class, Tag::class,
        Faq::class, Video::class, Review::class, MenuItem::class, SeoMeta::class, ModelRepairPage::class,
        DeviceModel::class, RepairType::class, RepairPrice::class, PartQuality::class,
        ServiceArea::class, ServiceCategory::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(ServiceDirectory::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Catch N+1 queries and mass-assignment mistakes during development and tests.
        Model::preventLazyLoading(! $this->app->isProduction());
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());

        if (filled($proxies = config('zr.trusted_proxies'))) {
            TrustProxies::at($proxies === '*' ? '*' : array_map('trim', explode(',', $proxies)));
        }

        Gate::policy(Activity::class, ActivityPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);

        Password::defaults(fn (): Password => $this->app->isProduction()
            ? Password::min(12)->mixedCase()->numbers()->uncompromised()
            : Password::min(8));

        $this->clearPageCacheOnContentChanges();
    }

    private function clearPageCacheOnContentChanges(): void
    {
        $flush = fn () => $this->app->make(PageCache::class)->flush();

        foreach (self::PUBLIC_CONTENT_MODELS as $model) {
            $model::saved($flush);
            $model::deleted($flush);
        }

        Event::listen(SettingsSaved::class, $flush);
    }
}
