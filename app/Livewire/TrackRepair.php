<?php

namespace App\Livewire;

use App\Models\RepairJob;
use App\Support\Messaging\MessageVariables;
use App\Support\Postcode;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;

/**
 * /track-repair: customers enter their job reference and postcode. Both
 * must match, and attempts are rate limited, so references cannot be guessed.
 */
class TrackRepair extends Component
{
    private const int MAX_ATTEMPTS_PER_MINUTE = 8;

    public string $reference = '';

    public string $postcode = '';

    public function mount(): void
    {
        $this->reference = mb_strtoupper(request()->string('ref')->trim()->limit(24, '')->toString());
    }

    public function track(): void
    {
        $this->validate([
            'reference' => ['required', 'string', 'max:24'],
            'postcode' => ['required', 'string', 'max:10'],
        ], [
            'reference.required' => 'Please enter your booking reference, e.g. ZR-2026-000123.',
            'postcode.required' => 'Please enter the postcode we collected from.',
        ]);

        $key = 'track-repair:'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS_PER_MINUTE)) {
            $this->addError('reference', 'Too many attempts. Please wait a minute and try again.');

            return;
        }

        RateLimiter::hit($key);

        $job = $this->find();

        if ($job === null) {
            $this->addError('reference', 'We could not find a repair with that reference and postcode. Please check both and try again.');

            return;
        }

        $this->redirect(MessageVariables::trackUrl($job));
    }

    public function render(): View
    {
        return view('livewire.track-repair');
    }

    private function find(): ?RepairJob
    {
        $postcode = Postcode::normalise($this->postcode);
        $reference = mb_strtoupper(trim($this->reference));

        if ($postcode === null) {
            return null;
        }

        return RepairJob::query()
            ->where('reference', $reference)
            ->whereHas('collectionAddress', fn ($address) => $address->where('postcode', $postcode))
            ->first();
    }
}
