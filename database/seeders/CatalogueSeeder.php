<?php

namespace Database\Seeders;

use App\Enums\DisplayType;
use App\Enums\FormType;
use App\Models\DeviceBrand;
use App\Models\DeviceModel;
use App\Models\PartQuality;
use App\Models\RepairPrice;
use App\Models\RepairType;
use App\Models\ServiceCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Service categories, iPhone models, repair types, part qualities and the
 * price matrix.
 *
 * Every price is seeded as a DRAFT ("DRAFT – confirm before launch" in the
 * admin). Safe to run repeatedly and in production: records are only created
 * when missing, so the owner's edits are never overwritten.
 */
class CatalogueSeeder extends Seeder
{
    /**
     * Lines of business. Only iPhone repair is live at launch.
     *
     * @var list<array{name: string, form_type: FormType, is_active: bool, description: string}>
     */
    private const array CATEGORIES = [
        ['name' => 'iPhone Repair', 'form_type' => FormType::Booking, 'is_active' => true, 'description' => 'iPhone repairs with collection from your door and return when the repair is done.'],
        ['name' => 'Phone Accessories', 'form_type' => FormType::Enquiry, 'is_active' => false, 'description' => 'Cases, screen protectors, chargers and cables.'],
        ['name' => 'Custom Printing', 'form_type' => FormType::Quote, 'is_active' => false, 'description' => 'Custom-printed T-shirts, mugs and gifts.'],
        ['name' => 'Web Development', 'form_type' => FormType::Quote, 'is_active' => false, 'description' => 'Websites and web applications for local businesses.'],
        ['name' => 'IT Services', 'form_type' => FormType::Enquiry, 'is_active' => false, 'description' => 'IT support and set-up for homes and small businesses.'],
    ];

    /**
     * @var list<array{slug: string, name: string, description: string}>
     */
    private const array PART_QUALITIES = [
        ['slug' => 'standard', 'name' => 'Standard', 'description' => 'A quality replacement part, fully tested before it leaves us. Best value.'],
        ['slug' => 'premium', 'name' => 'Premium', 'description' => 'Our top-grade part, closest to the original for colour, brightness and touch.'],
    ];

    /**
     * Repair types in display order. Icons live in public/images/icons.
     *
     * @var list<array{slug: string, name: string, short: string, icon: string, minutes: int|null, diagnosis: bool, popular: bool, summary: string}>
     */
    private const array REPAIR_TYPES = [
        ['slug' => 'screen-replacement', 'name' => 'Screen Replacement', 'short' => 'Screen', 'icon' => 'screen', 'minutes' => 45, 'diagnosis' => false, 'popular' => true,
            'summary' => 'Cracked glass, lines, black spots or a touchscreen that will not respond. We fit a new display and test touch, True Tone and Face ID before return.'],
        ['slug' => 'battery-replacement', 'name' => 'Battery Replacement', 'short' => 'Battery', 'icon' => 'battery', 'minutes' => 30, 'diagnosis' => false, 'popular' => true,
            'summary' => 'Draining fast, shutting down early or showing "Service" in Battery Health. A new battery brings back a full day of charge.'],
        ['slug' => 'charging-port-repair', 'name' => 'Charging Port Repair', 'short' => 'Charging port', 'icon' => 'charging', 'minutes' => 45, 'diagnosis' => false, 'popular' => true,
            'summary' => 'Loose cable, slow charging or not charging at all. We clean the port first and only replace it if cleaning does not fix the fault.'],
        ['slug' => 'back-glass-repair', 'name' => 'Back Glass Repair', 'short' => 'Back glass', 'icon' => 'backglass', 'minutes' => 90, 'diagnosis' => false, 'popular' => true,
            'summary' => 'Shattered rear glass replaced so your iPhone is safe to hold again and protected from dust and moisture.'],
        ['slug' => 'rear-camera-repair', 'name' => 'Rear Camera Repair', 'short' => 'Rear camera', 'icon' => 'camera', 'minutes' => 45, 'diagnosis' => false, 'popular' => false,
            'summary' => 'Blurry photos, a shaking or buzzing lens, or a black screen in the Camera app.'],
        ['slug' => 'front-camera-repair', 'name' => 'Front Camera Repair', 'short' => 'Front camera', 'icon' => 'camera', 'minutes' => 45, 'diagnosis' => false, 'popular' => false,
            'summary' => 'Front camera blurry or black in selfie mode and FaceTime.'],
        ['slug' => 'camera-lens-replacement', 'name' => 'Camera Lens Replacement', 'short' => 'Camera lens', 'icon' => 'camera', 'minutes' => 30, 'diagnosis' => false, 'popular' => false,
            'summary' => 'Cracked camera lens cover replaced for sharp, clear photos again.'],
        ['slug' => 'water-damage-repair', 'name' => 'Water Damage Repair', 'short' => 'Water damage', 'icon' => 'water', 'minutes' => 120, 'diagnosis' => true, 'popular' => false,
            'summary' => 'Dropped in water? Switch it off and do not charge it. We clean and assess the board, then tell you exactly what can be repaired and the cost.'],
        ['slug' => 'speaker-microphone-repair', 'name' => 'Speaker & Microphone Repair', 'short' => 'Speaker & mic', 'icon' => 'speaker', 'minutes' => 45, 'diagnosis' => false, 'popular' => false,
            'summary' => 'Quiet or crackly calls, muffled music, or people cannot hear you on calls.'],
        ['slug' => 'face-id-repair', 'name' => 'Face ID Repair', 'short' => 'Face ID', 'icon' => 'faceid', 'minutes' => 120, 'diagnosis' => true, 'popular' => false,
            'summary' => '"Face ID is not available" after a drop or repair. We diagnose the fault first and explain honestly whether it can be repaired.'],
        ['slug' => 'data-recovery', 'name' => 'Data Recovery', 'short' => 'Data recovery', 'icon' => 'data', 'minutes' => null, 'diagnosis' => true, 'popular' => false,
            'summary' => 'iPhone will not turn on and your photos are not backed up? We assess the phone and explain the options and cost before any work starts.'],
        ['slug' => 'diagnostics', 'name' => 'Diagnostics', 'short' => 'Diagnostics', 'icon' => 'diagnostics', 'minutes' => 30, 'diagnosis' => false, 'popular' => false,
            'summary' => 'Not sure what is wrong? We test every function and give you a clear, fixed quote before any repair.'],
    ];

    /**
     * iPhone models, newest first: name, family, release year, display, popular,
     * then draft prices in whole pounds:
     * [screen standard, screen premium, battery, charging port, back glass, rear camera, front camera, camera lens].
     *
     * @var list<array{0: string, 1: string, 2: int, 3: DisplayType, 4: bool, 5: array{int, int, int, int, int, int, int, int}}>
     */
    private const array MODELS = [
        ['iPhone 18 Pro Max', 'iPhone 18', 2026, DisplayType::Oled, false, [249, 349, 109, 99, 149, 229, 129, 49]],
        ['iPhone 18 Pro', 'iPhone 18', 2026, DisplayType::Oled, false, [229, 329, 105, 95, 139, 219, 119, 49]],
        ['iPhone 17e', 'iPhone 17', 2026, DisplayType::Oled, false, [129, 169, 79, 75, 89, 109, 79, 35]],
        ['iPhone 17 Pro Max', 'iPhone 17', 2025, DisplayType::Oled, true, [229, 319, 99, 95, 139, 209, 119, 45]],
        ['iPhone 17 Pro', 'iPhone 17', 2025, DisplayType::Oled, false, [209, 299, 95, 89, 129, 199, 109, 45]],
        ['iPhone Air', 'iPhone 17', 2025, DisplayType::Oled, false, [189, 259, 95, 89, 119, 169, 99, 39]],
        ['iPhone 17', 'iPhone 17', 2025, DisplayType::Oled, true, [169, 229, 89, 85, 109, 149, 95, 39]],
        ['iPhone 16e', 'iPhone 16', 2025, DisplayType::Oled, false, [119, 159, 75, 75, 89, 99, 79, 35]],
        ['iPhone 16 Pro Max', 'iPhone 16', 2024, DisplayType::Oled, false, [199, 289, 89, 89, 119, 189, 99, 45]],
        ['iPhone 16 Pro', 'iPhone 16', 2024, DisplayType::Oled, false, [189, 269, 85, 85, 109, 179, 95, 45]],
        ['iPhone 16 Plus', 'iPhone 16', 2024, DisplayType::Oled, false, [159, 219, 85, 79, 99, 139, 89, 39]],
        ['iPhone 16', 'iPhone 16', 2024, DisplayType::Oled, true, [149, 199, 79, 79, 99, 129, 85, 39]],
        ['iPhone 15 Pro Max', 'iPhone 15', 2023, DisplayType::Oled, false, [179, 259, 85, 85, 109, 169, 95, 39]],
        ['iPhone 15 Pro', 'iPhone 15', 2023, DisplayType::Oled, false, [169, 239, 79, 79, 99, 159, 89, 39]],
        ['iPhone 15 Plus', 'iPhone 15', 2023, DisplayType::Oled, false, [139, 189, 79, 75, 89, 119, 85, 35]],
        ['iPhone 15', 'iPhone 15', 2023, DisplayType::Oled, true, [129, 179, 75, 75, 89, 109, 79, 35]],
        ['iPhone 14 Pro Max', 'iPhone 14', 2022, DisplayType::Oled, false, [159, 229, 79, 75, 99, 149, 89, 35]],
        ['iPhone 14 Pro', 'iPhone 14', 2022, DisplayType::Oled, false, [149, 209, 75, 75, 89, 139, 85, 35]],
        ['iPhone 14 Plus', 'iPhone 14', 2022, DisplayType::Oled, false, [119, 169, 75, 69, 79, 99, 79, 35]],
        ['iPhone 14', 'iPhone 14', 2022, DisplayType::Oled, true, [109, 159, 69, 69, 79, 95, 75, 35]],
        ['iPhone SE (3rd generation)', 'iPhone SE', 2022, DisplayType::Lcd, false, [59, 79, 49, 49, 59, 69, 49, 29]],
        ['iPhone 13 Pro Max', 'iPhone 13', 2021, DisplayType::Oled, false, [129, 199, 69, 69, 89, 129, 79, 35]],
        ['iPhone 13 Pro', 'iPhone 13', 2021, DisplayType::Oled, false, [119, 179, 69, 65, 79, 119, 75, 35]],
        ['iPhone 13', 'iPhone 13', 2021, DisplayType::Oled, true, [99, 139, 65, 65, 75, 89, 69, 29]],
        ['iPhone 13 mini', 'iPhone 13', 2021, DisplayType::Oled, false, [99, 139, 65, 65, 69, 85, 69, 29]],
        ['iPhone 12 Pro Max', 'iPhone 12', 2020, DisplayType::Oled, false, [109, 169, 65, 65, 75, 109, 69, 29]],
        ['iPhone 12 Pro', 'iPhone 12', 2020, DisplayType::Oled, false, [95, 139, 59, 59, 69, 99, 65, 29]],
        ['iPhone 12', 'iPhone 12', 2020, DisplayType::Oled, true, [95, 139, 59, 59, 69, 79, 65, 29]],
        ['iPhone 12 mini', 'iPhone 12', 2020, DisplayType::Oled, false, [89, 129, 59, 59, 65, 75, 59, 29]],
        ['iPhone SE (2nd generation)', 'iPhone SE', 2020, DisplayType::Lcd, false, [55, 75, 45, 45, 55, 65, 45, 25]],
        ['iPhone 11 Pro Max', 'iPhone 11', 2019, DisplayType::Oled, false, [89, 129, 59, 55, 65, 89, 59, 29]],
        ['iPhone 11 Pro', 'iPhone 11', 2019, DisplayType::Oled, false, [85, 119, 55, 55, 65, 85, 59, 29]],
        ['iPhone 11', 'iPhone 11', 2019, DisplayType::Lcd, true, [65, 89, 55, 55, 65, 75, 55, 25]],
        ['iPhone XS Max', 'iPhone XS', 2018, DisplayType::Oled, false, [79, 119, 55, 55, 59, 75, 55, 25]],
        ['iPhone XS', 'iPhone XS', 2018, DisplayType::Oled, false, [75, 109, 49, 49, 59, 69, 49, 25]],
        ['iPhone XR', 'iPhone XR', 2018, DisplayType::Lcd, false, [59, 79, 49, 49, 59, 65, 49, 25]],
        ['iPhone X', 'iPhone X', 2017, DisplayType::Oled, false, [75, 105, 49, 49, 59, 65, 49, 25]],
        ['iPhone 8 Plus', 'iPhone 8', 2017, DisplayType::Lcd, false, [55, 69, 45, 45, 55, 59, 45, 25]],
        ['iPhone 8', 'iPhone 8', 2017, DisplayType::Lcd, false, [49, 65, 45, 45, 49, 55, 45, 25]],
    ];

    /**
     * Columns of MODELS[5] in order, with the warranty (days) for each.
     *
     * @var list<array{0: string, 1: string|null, 2: int}>
     */
    private const array MATRIX_COLUMNS = [
        ['screen-replacement', 'standard', 180],
        ['screen-replacement', 'premium', 365],
        ['battery-replacement', null, 365],
        ['charging-port-repair', null, 180],
        ['back-glass-repair', null, 180],
        ['rear-camera-repair', null, 180],
        ['front-camera-repair', null, 180],
        ['camera-lens-replacement', null, 180],
    ];

    public function run(): void
    {
        $iphoneRepair = $this->seedCategories();
        $qualities = $this->seedPartQualities();
        $repairTypes = $this->seedRepairTypes($iphoneRepair);

        $apple = DeviceBrand::query()->firstOrCreate(['slug' => DeviceBrand::APPLE], ['name' => 'Apple', 'sort' => 10]);

        foreach (self::MODELS as $index => [$name, $family, $year, $display, $popular, $prices]) {
            $model = DeviceModel::query()->firstOrCreate(['slug' => Str::slug($name)], [
                'device_brand_id' => $apple->getKey(),
                'name' => $name,
                'family' => $family,
                'release_year' => $year,
                'display_type' => $display,
                'is_popular' => $popular,
                'sort' => ($index + 1) * 10,
            ]);

            foreach (self::MATRIX_COLUMNS as $column => [$repairSlug, $qualitySlug, $warrantyDays]) {
                $this->seedPrice($model, $repairTypes[$repairSlug], $qualitySlug ? $qualities[$qualitySlug] : null, $prices[$column], $warrantyDays);
            }

            foreach ($this->diagnosisPrices($name, $year) as $repairSlug => [$pounds, $warrantyDays]) {
                $this->seedPrice($model, $repairTypes[$repairSlug], null, $pounds, $warrantyDays);
            }
        }
    }

    /**
     * Prices that do not vary much by model: speaker, water damage, Face ID,
     * data recovery and diagnostics. Touch ID models have no Face ID.
     *
     * @return array<string, array{int, int}> repair slug => [pounds, warranty days]
     */
    private function diagnosisPrices(string $name, int $year): array
    {
        $prices = [
            'speaker-microphone-repair' => [match (true) {
                $year >= 2025 => 69,
                $year >= 2023 => 59,
                $year >= 2020 => 49,
                default => 39,
            }, 180],
            'water-damage-repair' => [$year >= 2021 ? 59 : 49, 90],
            'data-recovery' => [99, 0],
            'diagnostics' => [20, 0],
        ];

        $hasTouchId = str_starts_with($name, 'iPhone 8') || str_starts_with($name, 'iPhone SE');

        if (! $hasTouchId) {
            $prices['face-id-repair'] = [match (true) {
                $year >= 2024 => 149,
                $year >= 2021 => 129,
                default => 99,
            }, 180];
        }

        return $prices;
    }

    private function seedCategories(): ServiceCategory
    {
        foreach (self::CATEGORIES as $index => $category) {
            ServiceCategory::query()->firstOrCreate(['slug' => Str::slug($category['name'])], [
                ...$category,
                'sort' => ($index + 1) * 10,
            ]);
        }

        return ServiceCategory::query()->where('slug', ServiceCategory::IPHONE_REPAIR)->firstOrFail();
    }

    /**
     * @return array<string, PartQuality>
     */
    private function seedPartQualities(): array
    {
        $qualities = [];

        foreach (self::PART_QUALITIES as $index => $quality) {
            $qualities[$quality['slug']] = PartQuality::query()->firstOrCreate(['slug' => $quality['slug']], [
                'name' => $quality['name'],
                'description' => $quality['description'],
                'sort' => ($index + 1) * 10,
            ]);
        }

        return $qualities;
    }

    /**
     * @return array<string, RepairType>
     */
    private function seedRepairTypes(ServiceCategory $category): array
    {
        $types = [];

        foreach (self::REPAIR_TYPES as $index => $type) {
            $types[$type['slug']] = RepairType::query()->firstOrCreate(['slug' => $type['slug']], [
                'service_category_id' => $category->getKey(),
                'name' => $type['name'],
                'short_name' => $type['short'],
                'summary' => $type['summary'],
                'icon' => $type['icon'],
                'typical_duration_minutes' => $type['minutes'],
                'requires_diagnosis' => $type['diagnosis'],
                'is_popular' => $type['popular'],
                'sort' => ($index + 1) * 10,
            ]);
        }

        return $types;
    }

    private function seedPrice(DeviceModel $model, RepairType $repairType, ?PartQuality $quality, int $pounds, int $warrantyDays): void
    {
        RepairPrice::query()->firstOrCreate([
            'device_model_id' => $model->getKey(),
            'repair_type_id' => $repairType->getKey(),
            'part_quality_id' => $quality?->getKey(),
        ], [
            'price_pence' => $pounds * 100,
            'warranty_days' => $warrantyDays,
            'is_draft' => true,
        ]);
    }
}
