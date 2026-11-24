<?php

namespace App\Http\Controllers;

use App\Enums\SystemPage;
use App\Models\DeviceModel;
use App\Models\Faq;
use App\Models\ModelRepairPage;
use App\Models\Page;
use App\Models\Post;
use App\Models\RepairPrice;
use App\Models\RepairType;
use App\Models\Service;
use App\Models\ServiceArea;
use App\Models\Video;
use App\Services\Content\ServiceDirectory;
use App\Services\Pricing\PricingService;
use App\Services\Seo\SchemaGraph;
use App\Services\Seo\SeoResolver;
use App\Settings\SiteSettings;
use App\Support\BookingCallToAction;
use App\Support\Content\BlockRenderer;
use App\Support\Money;
use App\Support\PublicFile;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * /iphone-repair (the pillar page), /iphone-repair/{slug} (a service page or
 * a model page; service pages are checked first) and the optional
 * /iphone-repair/{model}/{repair} pages.
 */
class RepairController extends Controller
{
    public function __construct(
        private readonly ServiceDirectory $directory,
        private readonly PricingService $pricing,
        private readonly SeoResolver $seo,
        private readonly SchemaGraph $schema,
        private readonly BlockRenderer $renderer,
        private readonly SiteSettings $site,
        private readonly BookingCallToAction $booking,
    ) {}

    public function index(): View
    {
        $page = Page::system(SystemPage::IphoneRepair);
        $page->loadMissing(['faqs' => fn ($faqs) => $faqs->where('is_active', true)]);

        $services = $this->directory->services();
        $models = $this->pricing->modelsWithPrices();
        $breadcrumbs = [['label' => 'iPhone Repair']];

        $seo = $this->seo->forPage($page, $breadcrumbs, pageType: 'CollectionPage')
            ->withSchema([
                $this->schema->serviceNode(
                    name: 'iPhone Repair',
                    description: (string) $page->intro,
                    url: route('repairs.index'),
                    pricesPence: $this->pricing->fromPrices(),
                ),
                $this->schema->faqNode($page->faqs, route('repairs.index')),
            ]);

        return view('pages.repairs.index', [
            'seo' => $seo,
            'page' => $page,
            'content' => $this->renderer->render($page->blocks),
            'breadcrumbs' => $breadcrumbs,
            'services' => $services->map(fn (Service $service): array => ['service' => $service, 'from_pence' => $this->directory->fromPrice($service)]),
            'popularModels' => $models->where('is_popular', true)->values(),
            'modelFamilies' => $models->groupBy(fn (DeviceModel $model): string => $model->family ?? 'Other'),
            'posts' => Post::query()->live()->latestFirst()->with('category')->limit(3)->get(),
            'areas' => ServiceArea::query()->listed()->get(),
            'faqs' => $page->faqs,
        ]);
    }

    public function show(string $slug): View
    {
        $service = Service::query()->listed()->where('slug', $slug)
            ->with(['seoMeta', 'faqs' => fn ($faqs) => $faqs->where('is_active', true), 'repairTypes' => fn ($query) => $query->where('is_active', true)])
            ->first();

        if ($service !== null) {
            return $this->service($service);
        }

        $model = DeviceModel::query()->listed()->where('slug', $slug)
            ->whereHas('prices', fn (Builder $prices) => $prices->available())
            ->with(['seoMeta', 'faqs' => fn ($faqs) => $faqs->where('is_active', true), 'repairPages' => fn ($pages) => $pages->where('is_enabled', true)])
            ->firstOrFail();

        return $this->model($model);
    }

    public function modelRepair(string $model, string $repair): View
    {
        $deviceModel = DeviceModel::query()->listed()->where('slug', $model)->firstOrFail();
        $repairType = RepairType::query()->listed()->where('slug', $repair)->firstOrFail();

        $page = ModelRepairPage::query()
            ->where('device_model_id', $deviceModel->getKey())
            ->where('repair_type_id', $repairType->getKey())
            ->where('is_enabled', true)
            ->firstOrFail();

        $prices = RepairPrice::query()->available()
            ->where('device_model_id', $deviceModel->getKey())
            ->where('repair_type_id', $repairType->getKey())
            ->with('partQuality')
            ->get()
            ->sortBy(fn (RepairPrice $price): int => $price->partQuality->sort ?? 0)
            ->values();

        abort_if($prices->isEmpty(), 404);

        $fromPence = (int) $prices->min(fn (RepairPrice $price): int => $price->effectivePricePence());
        $service = $this->directory->forRepairType($repairType);
        $canonical = route('repairs.model-repair', [$deviceModel->slug, $repairType->slug]);
        $passes = $page->passesContentChecks();
        $breadcrumbs = [
            ['label' => 'iPhone Repair', 'url' => route('repairs.index')],
            ['label' => $deviceModel->name, 'url' => $deviceModel->url()],
            ['label' => $repairType->name],
        ];

        $seo = $this->seo->make(
            meta: null,
            title: $this->seo->pattern($this->site->title_pattern_model_repair, [
                'repair' => $repairType->name,
                'model' => $deviceModel->name,
                'price' => Money::format($fromPence),
            ]),
            description: "{$repairType->name} for {$deviceModel->name} in Blackburn from ".Money::format($fromPence).'. We collect, repair and return your iPhone, with a warranty on every repair.',
            image: $service?->og_image,
            noindex: ! $passes,
            // Until its own content passes the checks, the model page is the canonical version.
            canonical: $passes ? $canonical : $deviceModel->url(),
            breadcrumbs: $breadcrumbs,
            schema: [$this->schema->serviceNode("{$repairType->name} for {$deviceModel->name}", (string) $repairType->summary, $canonical, $prices->map(fn (RepairPrice $price): int => $price->effectivePricePence()))],
        );

        return view('pages.repairs.model-repair', [
            'seo' => $seo,
            'model' => $deviceModel,
            'repair' => $repairType,
            'page' => $page,
            'content' => $this->renderer->render($page->blocks),
            'prices' => $prices,
            'fromPence' => $fromPence,
            'service' => $service,
            'breadcrumbs' => $breadcrumbs,
            'bookingUrl' => $this->booking->url($deviceModel, $repairType),
        ]);
    }

    private function service(Service $service): View
    {
        $repairTypeIds = $service->repairTypes->modelKeys();

        $prices = RepairPrice::query()->available()
            ->whereIn('repair_type_id', $repairTypeIds)
            ->whereHas('deviceModel', fn (Builder $models) => $models->where('is_active', true))
            ->with(['deviceModel', 'partQuality', 'repairType'])
            ->get();

        $fromPence = $prices->isEmpty() ? null : (int) $prices->min(fn (RepairPrice $price): int => $price->effectivePricePence());

        // One row per model (newest first), with that model's prices for each repair on this page.
        $priceRows = $prices
            ->groupBy('device_model_id')
            ->map(fn (Collection $modelPrices): array => [
                'model' => $modelPrices->first()->deviceModel,
                'prices' => $modelPrices->sortBy(fn (RepairPrice $price): array => [$price->repairType->sort, $price->partQuality->sort ?? 0])->values(),
            ])
            ->sortBy(fn (array $row): array => [$row['model']->sort, $row['model']->name])
            ->values();

        $canonical = $service->url();
        $breadcrumbs = [
            ['label' => 'iPhone Repair', 'url' => route('repairs.index')],
            ['label' => $service->name],
        ];

        /** @var Collection<int, Faq> $faqs */
        $faqs = $service->faqs;

        $seo = $this->seo->make(
            meta: $service->seoMeta,
            title: $this->seo->pattern($this->site->title_pattern_service, [
                'name' => 'iPhone '.$service->name,
                'price' => $fromPence !== null ? Money::format($fromPence) : null,
            ]),
            description: $service->summary,
            image: $service->og_image,
            imageAlt: "iPhone {$service->name} by ZR Technologies",
            canonical: $canonical,
            breadcrumbs: $breadcrumbs,
            schema: [
                $this->schema->serviceNode('iPhone '.$service->name, (string) $service->summary, $canonical, $prices->map(fn (RepairPrice $price): int => $price->effectivePricePence()), PublicFile::url($service->og_image)),
                $this->schema->faqNode($faqs, $canonical),
            ],
        );

        $primaryRepair = $service->repairTypes->first();

        return view('pages.repairs.service', [
            'seo' => $seo,
            'service' => $service,
            'content' => $this->renderer->render($service->blocks),
            'breadcrumbs' => $breadcrumbs,
            'fromPence' => $fromPence,
            'priceRows' => $priceRows,
            'repairTypes' => $service->repairTypes,
            'faqs' => $faqs,
            'otherServices' => $this->directory->services()->reject(fn (Service $other): bool => $other->is($service))->values(),
            'videos' => Video::query()->listed()->where('service_id', $service->getKey())->limit(4)->get(),
            'posts' => Post::query()->live()->latestFirst()->where('service_id', $service->getKey())->with('category')->limit(3)->get(),
            'bookingUrl' => $this->booking->url(repair: $primaryRepair),
        ]);
    }

    private function model(DeviceModel $model): View
    {
        $pricesByRepair = $this->pricing->pricesForModel($model);
        $allPrices = $pricesByRepair->flatten(1);
        // "From" means the cheapest actual repair, not the diagnostic check.
        $repairPrices = $allPrices->reject(fn (RepairPrice $price): bool => $price->repairType->slug === RepairType::DIAGNOSTICS);
        $fromPence = (int) ($repairPrices->isNotEmpty() ? $repairPrices : $allPrices)->min(fn (RepairPrice $price): int => $price->effectivePricePence());
        $canonical = $model->url();
        $breadcrumbs = [
            ['label' => 'iPhone Repair', 'url' => route('repairs.index')],
            ['label' => $model->name],
        ];

        /** @var Collection<int, Faq> $faqs */
        $faqs = $model->faqs->isNotEmpty()
            ? $model->faqs
            : Faq::query()->listed()->where('topic', 'repairs')->limit(4)->get();

        $seo = $this->seo->make(
            meta: $model->seoMeta,
            title: $this->seo->pattern($this->site->title_pattern_model, [
                'model' => $model->name,
                'price' => Money::format($fromPence),
            ]),
            description: "{$model->name} repair in Blackburn from ".Money::format($fromPence).': screen, battery, charging port, cameras and more. We collect, repair and return your iPhone, with a warranty.',
            image: 'images/og/og-iphone-repair.jpg',
            imageAlt: "{$model->name} repair by ZR Technologies",
            canonical: $canonical,
            breadcrumbs: $breadcrumbs,
            schema: [
                $this->schema->serviceNode("{$model->name} Repair", "Repairs for the {$model->name}, with collection and return.", $canonical, $allPrices->map(fn (RepairPrice $price): int => $price->effectivePricePence())),
                $this->schema->faqNode($faqs, $canonical),
            ],
        );

        $relatedModels = DeviceModel::query()->listed()
            ->whereKeyNot($model->getKey())
            ->whereHas('prices', fn (Builder $prices) => $prices->available())
            ->where(fn (Builder $query) => $query->where('family', $model->family)->orWhere('is_popular', true))
            ->limit(8)
            ->get();

        return view('pages.repairs.model', [
            'seo' => $seo,
            'model' => $model,
            'content' => $this->renderer->render($model->blocks),
            'breadcrumbs' => $breadcrumbs,
            'pricesByRepair' => $pricesByRepair,
            'fromPence' => $fromPence,
            'directory' => $this->directory,
            'repairPageSlugs' => $model->repairPages->pluck('repair_type_id')->all(),
            'faqs' => $faqs,
            'relatedModels' => $relatedModels,
            'bookingUrl' => $this->booking->url($model),
        ]);
    }
}
