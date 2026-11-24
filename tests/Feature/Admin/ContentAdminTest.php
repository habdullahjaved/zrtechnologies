<?php

use App\Enums\PublishStatus;
use App\Enums\StaffRole;
use App\Enums\SystemPage;
use App\Filament\Pages\Settings\ManageSiteSettings;
use App\Filament\Resources\Authors\AuthorResource;
use App\Filament\Resources\DeviceModels\Pages\EditDeviceModel;
use App\Filament\Resources\Faqs\FaqResource;
use App\Filament\Resources\MenuItems\MenuItemResource;
use App\Filament\Resources\NotFoundLogs\NotFoundLogResource;
use App\Filament\Resources\NotFoundLogs\Pages\ListNotFoundLogs;
use App\Filament\Resources\Pages\PageResource;
use App\Filament\Resources\Pages\Pages\CreatePage;
use App\Filament\Resources\Pages\Pages\EditPage;
use App\Filament\Resources\PostCategories\PostCategoryResource;
use App\Filament\Resources\Posts\Pages\CreatePost;
use App\Filament\Resources\Posts\PostResource;
use App\Filament\Resources\Redirects\RedirectResource;
use App\Filament\Resources\Reviews\ReviewResource;
use App\Filament\Resources\ServiceAreas\Pages\EditServiceArea;
use App\Filament\Resources\Services\Pages\EditService;
use App\Filament\Resources\Services\ServiceResource;
use App\Filament\Resources\Videos\VideoResource;
use App\Models\DeviceModel;
use App\Models\NotFoundLog;
use App\Models\Page;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Redirect;
use App\Models\Service;
use App\Models\ServiceArea;
use App\Models\User;
use App\Settings\SiteSettings;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Actions\DeleteAction;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    config(['zr.admin.require_mfa' => false]);
});

it('gives the content team the content and SEO screens, and keeps technicians out', function (StaffRole $role, bool $content, bool $reviews) {
    $this->actingAs(User::factory()->withRole($role)->create());

    foreach ([PageResource::class, ServiceResource::class, PostResource::class, PostCategoryResource::class, AuthorResource::class, FaqResource::class, VideoResource::class, MenuItemResource::class, RedirectResource::class, NotFoundLogResource::class] as $resource) {
        $this->get($resource::getUrl('index'))->assertStatus($content ? 200 : 403);
    }

    $this->get(ReviewResource::getUrl('index'))->assertStatus($reviews ? 200 : 403);
})->with([
    'manager' => [StaffRole::Manager, true, true],
    'content editor' => [StaffRole::ContentEditor, true, true],
    'sales/support' => [StaffRole::SalesSupport, false, true],
    'technician' => [StaffRole::Technician, false, false],
]);

it('opens the editors with the SEO preview and checklist', function () {
    $this->actingAs(User::factory()->withRole(StaffRole::ContentEditor)->create());
    $page = Page::factory()->system(SystemPage::About)->create();
    $service = Service::factory()->create();

    $this->get(PageResource::getUrl('edit', ['record' => $page]))
        ->assertOk()
        ->assertSee('Google preview')
        ->assertSee('SEO checklist');

    $this->get(ServiceResource::getUrl('edit', ['record' => $service]))->assertOk();
    $this->get(PostResource::getUrl('create'))->assertOk()->assertSee('Internal link suggestions');
});

it('creates a custom page with content blocks and SEO overrides', function () {
    $this->actingAs(User::factory()->withRole(StaffRole::ContentEditor)->create());

    livewire(CreatePage::class)
        ->fillForm([
            'title' => 'Careers',
            'slug' => 'careers',
            'intro' => 'Join our repair team.',
            'status' => PublishStatus::Published,
            'blocks' => [['type' => 'heading', 'data' => ['text' => 'Open roles', 'level' => 'h2']]],
            'seoMeta.title' => 'Jobs at ZR Technologies',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $page = Page::query()->where('slug', 'careers')->sole();

    expect($page->seoMeta->title)->toBe('Jobs at ZR Technologies');

    $this->get('/careers')->assertOk()->assertSee('Open roles')->assertSee('<title>Jobs at ZR Technologies</title>', false);
});

it('stops custom pages taking an address the site already uses', function () {
    $this->actingAs(User::factory()->withRole(StaffRole::ContentEditor)->create());

    livewire(CreatePage::class)
        ->fillForm(['title' => 'Contact', 'slug' => 'contact', 'status' => PublishStatus::Draft])
        ->call('create')
        ->assertHasFormErrors(['slug']);
});

it('does not offer to delete system pages', function () {
    $this->actingAs(User::factory()->withRole(StaffRole::SuperAdmin)->create());
    $page = Page::factory()->system(SystemPage::Privacy)->create();

    livewire(EditPage::class, ['record' => $page->getRouteKey()])->assertActionHidden(DeleteAction::class);
});

it('keeps repair page and model slugs from clashing', function () {
    $this->actingAs(User::factory()->withRole(StaffRole::Manager)->create());
    DeviceModel::factory()->create(['slug' => 'iphone-15']);
    $service = Service::factory()->create();

    livewire(EditService::class, ['record' => $service->getRouteKey()])
        ->fillForm(['slug' => 'iphone-15'])
        ->call('save')
        ->assertHasFormErrors(['slug']);
});

it('requires a future date for scheduled posts and alt text for featured images', function () {
    $this->actingAs(User::factory()->withRole(StaffRole::ContentEditor)->create());
    $category = PostCategory::factory()->create();

    livewire(CreatePost::class)
        ->fillForm([
            'title' => 'Battery tips',
            'slug' => 'battery-tips',
            'post_category_id' => $category->id,
            'status' => PublishStatus::Scheduled,
            'published_at' => now()->subDay(),
        ])
        ->call('create')
        ->assertHasFormErrors(['published_at']);

    livewire(CreatePost::class)
        ->fillForm([
            'title' => 'Battery tips',
            'slug' => 'battery-tips',
            'post_category_id' => $category->id,
            'status' => PublishStatus::Scheduled,
            'published_at' => now()->addDay(),
            'blocks' => [['type' => 'rich_text', 'data' => ['content' => '<p>Keep your battery cool.</p>']]],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Post::query()->where('slug', 'battery-tips')->sole()->isLive())->toBeFalse();
});

it('creates a redirect from the 404 log', function () {
    $this->actingAs(User::factory()->withRole(StaffRole::ContentEditor)->create());
    $log = NotFoundLog::factory()->create(['path' => '/iphone-screen-repair']);

    livewire(ListNotFoundLogs::class)
        ->callTableAction('redirect', $log, ['to_path' => '/iphone-repair/screen-replacement', 'status_code' => 301, 'is_active' => true])
        ->assertHasNoTableActionErrors();

    expect(Redirect::query()->where('from_path', '/iphone-screen-repair')->sole()->to_path)->toBe('/iphone-repair/screen-replacement')
        ->and($log->fresh()->redirect_id)->not->toBeNull();
});

it('shows the location page content check on the service area form', function () {
    $this->actingAs(User::factory()->withRole(StaffRole::Manager)->create());
    $area = ServiceArea::factory()->create(['intro' => 'Short.']);

    livewire(EditServiceArea::class, ['record' => $area->getRouteKey()])
        ->assertSee('Hidden from search engines until the checks below pass');
});

it('edits model page content and opens its model × repair pages', function () {
    $this->actingAs(User::factory()->withRole(StaffRole::Manager)->create());
    $model = DeviceModel::factory()->create();

    livewire(EditDeviceModel::class, ['record' => $model->getRouteKey()])
        ->fillForm(['intro' => 'Our most popular model to repair.'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($model->fresh()->intro)->toBe('Our most popular model to repair.');
});

it('saves title patterns and thin-content thresholds in Website & SEO settings', function () {
    $this->actingAs(User::factory()->withRole(StaffRole::Manager)->create());

    livewire(ManageSiteSettings::class)
        ->fillForm(['title_pattern_location' => 'iPhone Repairs {town}', 'min_unique_words_location' => 450])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(freshSettings(SiteSettings::class)->title_pattern_location)->toBe('iPhone Repairs {town}');
});
