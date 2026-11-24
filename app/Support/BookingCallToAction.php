<?php

namespace App\Support;

use App\Models\DeviceModel;
use App\Models\PartQuality;
use App\Models\RepairType;
use App\Settings\BusinessSettings;
use Illuminate\Support\Facades\Route;

/**
 * Where "Book a Repair" buttons point. Once the online booking wizard exists
 * (route "booking.create") they go there, prefilled with any model, repair or
 * postcode already chosen; until then they open WhatsApp with a prefilled
 * booking message, so the button always works.
 */
final class BookingCallToAction
{
    public const string ROUTE = 'booking.create';

    public function __construct(private BusinessSettings $business) {}

    public function url(?DeviceModel $model = null, ?RepairType $repair = null, ?PartQuality $quality = null, ?string $postcode = null): string
    {
        if (Route::has(self::ROUTE)) {
            return route(self::ROUTE, array_filter([
                'model' => $model?->slug,
                'repair' => $repair?->slug,
                'quality' => $quality?->slug,
                'postcode' => $postcode,
            ]));
        }

        return Phone::whatsAppUrl($this->business->whatsapp, $this->message($model, $repair, $quality, $postcode));
    }

    public function opensWhatsApp(): bool
    {
        return ! Route::has(self::ROUTE);
    }

    /**
     * e.g. "Hi ZR Technologies, I'd like to book a Screen Replacement (Premium) for my iPhone 15. My postcode is BB2 4NY."
     */
    private function message(?DeviceModel $model, ?RepairType $repair, ?PartQuality $quality, ?string $postcode): string
    {
        $greeting = "Hi {$this->business->trading_name}, ";

        $request = match (true) {
            $repair !== null && $model !== null => "I'd like to book a {$repair->name}".($quality ? " ({$quality->name})" : '')." for my {$model->name}.",
            $repair !== null => "I'd like to book a {$repair->name} for my iPhone.",
            $model !== null => "I'd like to book a repair for my {$model->name}.",
            default => "I'd like to book an iPhone repair.",
        };

        return $greeting.$request.($postcode ? " My postcode is {$postcode}." : '');
    }
}
