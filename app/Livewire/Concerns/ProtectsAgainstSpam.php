<?php

namespace App\Livewire\Concerns;

use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Locked;

/**
 * Low-friction spam protection for public forms, with no CAPTCHA:
 *  - a honeypot field people never see (bots fill it in);
 *  - a minimum time between the form loading and being sent;
 *  - a per-IP rate limit.
 *
 * Components render <x-form.honeypot /> inside the form and call
 * passesSpamChecks() before saving.
 */
trait ProtectsAgainstSpam
{
    /** Honeypot: hidden from people, so it must stay empty. */
    public string $website = '';

    #[Locked]
    public int $formLoadedAt = 0;

    public function mountProtectsAgainstSpam(): void
    {
        $this->formLoadedAt = now()->getTimestamp();
    }

    /**
     * @param  string  $limiterKey  e.g. "enquiry"; the visitor's IP is appended.
     */
    protected function passesSpamChecks(string $limiterKey, int $maxAttempts = 5, int $decaySeconds = 600, int $minimumSeconds = 3, string $errorField = 'form'): bool
    {
        if ($this->website !== '' || now()->getTimestamp() - $this->formLoadedAt < $minimumSeconds) {
            $this->addError($errorField, 'Sorry, we could not send that. Please wait a moment and try again.');

            return false;
        }

        $key = $limiterKey.':'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $minutes = (int) ceil(RateLimiter::availableIn($key) / 60);
            $this->addError($errorField, "You have sent this a few times already. Please try again in {$minutes} ".($minutes === 1 ? 'minute' : 'minutes').', or call us.');

            return false;
        }

        RateLimiter::hit($key, $decaySeconds);

        return true;
    }
}
