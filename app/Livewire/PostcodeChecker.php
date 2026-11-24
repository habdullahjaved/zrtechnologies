<?php

namespace App\Livewire;

use App\Enums\CoverageStatus;
use App\Services\Availability\AvailabilityService;
use App\Services\Coverage\CoverageService;
use App\Settings\BookingSettings;
use App\Settings\BusinessSettings;
use App\Support\BookingCallToAction;
use App\Support\Phone;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * "Do you collect from me?" Checks a postcode against the active service
 * areas and, when covered, shows the fee and the next collection slot.
 */
class PostcodeChecker extends Component
{
    private const int MAX_CHECKS_PER_MINUTE = 15;

    public string $postcode = '';

    /** "hero" for dark backgrounds, "panel" for light ones. */
    #[Locked]
    public string $variant = 'panel';

    /** HTML id prefix, so the checker can appear more than once on a page. */
    #[Locked]
    public string $idPrefix = 'postcode-checker';

    /**
     * The last answer, kept as plain values so Livewire can serialise it.
     *
     * @var array{status: string, postcode: string, area: string|null, fee: string|null, next_slot: string|null, booking_url: string, verified: bool}|null
     */
    #[Locked]
    public ?array $result = null;

    public function mount(string $variant = 'panel', string $idPrefix = 'postcode-checker'): void
    {
        $this->variant = in_array($variant, ['hero', 'panel'], true) ? $variant : 'panel';
        $this->idPrefix = $idPrefix;
    }

    public function check(CoverageService $coverage, AvailabilityService $availability, BookingCallToAction $booking, BusinessSettings $business): void
    {
        $this->result = null;

        $this->validate(
            ['postcode' => ['required', 'string', 'max:10']],
            ['postcode.required' => 'Please enter your postcode.', 'postcode.max' => 'Please enter a UK postcode, e.g. BB2 4NY.'],
        );

        $throttleKey = 'postcode-check:'.request()->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_CHECKS_PER_MINUTE)) {
            $this->addError('postcode', 'Too many checks in a short time. Please wait a minute and try again.');

            return;
        }

        RateLimiter::hit($throttleKey);

        $answer = $coverage->check($this->postcode);

        if ($answer->status === CoverageStatus::Invalid) {
            $this->addError('postcode', 'We could not find that postcode. Please check it and try again, e.g. BB2 4NY.');

            return;
        }

        $this->postcode = (string) $answer->postcode;
        $nextSlot = $answer->isCovered() ? $availability->nextSlot() : null;

        $this->result = [
            'status' => $answer->status->value,
            'postcode' => (string) $answer->postcode,
            'area' => $answer->area?->name,
            'fee' => $answer->area?->collectionFeeLabel(),
            'next_slot' => $nextSlot?->label(),
            'booking_url' => $answer->isCovered()
                ? $booking->url(postcode: $answer->postcode)
                : Phone::whatsAppUrl($business->whatsapp, "Hi {$business->trading_name}, my postcode is {$answer->postcode}. Can you help with an iPhone repair?"),
            'verified' => $answer->verified,
        ];
    }

    public function resetChecker(): void
    {
        $this->reset('postcode', 'result');
        $this->resetErrorBag();
    }

    public function render(BookingSettings $settings, BookingCallToAction $booking): View
    {
        return view('livewire.postcode-checker', [
            'postInEnabled' => $settings->post_in_enabled,
            'bookingOpensWhatsApp' => $booking->opensWhatsApp(),
        ]);
    }
}
