<?php

namespace App\Livewire;

use App\Actions\Booking\CreateBooking;
use App\Actions\Booking\SavePartialBooking;
use App\Actions\Leads\CaptureLead;
use App\Data\BookingData;
use App\Data\CollectionSlot;
use App\Data\LeadData;
use App\Enums\CoverageStatus;
use App\Enums\LeadType;
use App\Livewire\Concerns\ProtectsAgainstSpam;
use App\Models\DeviceModel;
use App\Models\Lead;
use App\Models\PartQuality;
use App\Models\RepairPrice;
use App\Models\RepairType;
use App\Services\Attribution\AttributionTracker;
use App\Services\Availability\AvailabilityService;
use App\Services\Coverage\CoverageService;
use App\Services\Pricing\PricingService;
use App\Settings\BookingSettings;
use App\Support\Postcode;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Session;
use Livewire\Component;
use Propaganistas\LaravelPhone\Rules\Phone as PhoneRule;

/**
 * The six-step online booking (brief §5):
 * 1 postcode → 2 device → 3 repairs → 4 collection slot → 5 details → 6 review.
 *
 * Nothing the browser sends is trusted: coverage, prices and slots are
 * checked again on the server when the booking is saved. Once a mobile or
 * email is entered, the booking so far is kept as a lead so the team can
 * help anyone who gets stuck.
 *
 * @property-read Collection<int, DeviceModel> $models
 * @property-read DeviceModel|null $selectedModel
 * @property-read SupportCollection<int, Collection<int, RepairPrice>> $prices
 * @property-read list<CollectionSlot> $collectionSlots
 * @property-read list<array{label: string, quality: string|null}> $summaryLines
 */
class BookingWizard extends Component
{
    use ProtectsAgainstSpam;

    public const int STEPS = 6;

    /** @var array<int, string> */
    public const array STEP_LABELS = [1 => 'Postcode', 2 => 'Device', 3 => 'Repair', 4 => 'Collection', 5 => 'Your details', 6 => 'Confirm'];

    #[Locked]
    #[Session]
    public int $step = 1;

    /** Highest step reached, so customers can jump back but not skip ahead. */
    #[Locked]
    #[Session]
    public int $furthestStep = 1;

    // Step 1: postcode.
    #[Session]
    public string $postcode = '';

    #[Locked]
    #[Session]
    public ?int $serviceAreaId = null;

    #[Locked]
    #[Session]
    public ?string $areaName = null;

    #[Locked]
    #[Session]
    public int $collectionFeePence = 0;

    #[Locked]
    #[Session]
    public bool $outOfArea = false;

    // Out of area: request a call back or post-in repair.
    #[Session]
    public string $outOfAreaName = '';

    #[Session]
    public string $outOfAreaPhone = '';

    #[Session]
    public string $outOfAreaEmail = '';

    #[Session]
    public string $outOfAreaMessage = '';

    #[Locked]
    #[Session]
    public ?string $outOfAreaReference = null;

    // Step 2: device.
    #[Session]
    public string $model = '';

    #[Session]
    public string $colour = '';

    #[Session]
    public string $storage = '';

    // Step 3: repairs (repair type ids) and the part quality chosen for each.

    /** @var list<string> */
    #[Session]
    public array $selectedRepairs = [];

    /** @var array<int|string, string> repair type id => part quality id ('' for none) */
    #[Session]
    public array $qualities = [];

    // Step 4: collection.
    #[Session]
    public string $slot = '';

    #[Session]
    public string $selectedDate = '';

    #[Session]
    public string $returnTo = 'same';

    #[Session]
    public string $returnLine1 = '';

    #[Session]
    public string $returnLine2 = '';

    #[Session]
    public string $returnTown = '';

    #[Session]
    public string $returnPostcode = '';

    // Step 5: details.
    #[Session]
    public string $firstName = '';

    #[Session]
    public string $lastName = '';

    #[Session]
    public string $phone = '';

    #[Session]
    public string $email = '';

    #[Session]
    public string $line1 = '';

    #[Session]
    public string $line2 = '';

    #[Session]
    public string $town = '';

    #[Session]
    public string $notes = '';

    #[Session]
    public bool $acceptTerms = false;

    #[Session]
    public bool $startStraightAway = false;

    #[Session]
    public bool $marketingConsent = false;

    /** The lead saved while the customer fills in the form. */
    #[Locked]
    #[Session]
    public ?int $leadId = null;

    public function mount(): void
    {
        if ($this->postcode !== '' || $this->model !== '' || $this->step > 1) {
            return;
        }

        $request = request();
        $postcode = $request->string('postcode')->trim()->toString();
        $modelSlug = $request->string('model')->toString();
        $repairSlug = $request->string('repair')->toString();
        $qualitySlug = $request->string('quality')->toString();

        if ($modelSlug !== '' && $this->models->contains('slug', $modelSlug)) {
            $this->model = $modelSlug;
        }

        if ($repairSlug !== '' && $this->model !== '') {
            $this->preselectRepair($repairSlug, $qualitySlug);
        }

        if ($postcode !== '' && mb_strlen($postcode) <= 10) {
            $this->postcode = $postcode;
            $this->runCoverageCheck(quiet: true);
        }
    }

    /**
     * Step 1: do we collect from this postcode?
     */
    public function checkPostcode(): void
    {
        $this->runCoverageCheck(quiet: false);
    }

    private function runCoverageCheck(bool $quiet): void
    {
        $this->resetErrorBag('postcode');
        $this->outOfArea = false;

        $this->validate(
            ['postcode' => ['required', 'string', 'max:10']],
            ['postcode.required' => 'Please enter your postcode.', 'postcode.max' => 'Please enter a UK postcode, e.g. BB2 4NY.'],
        );

        $throttleKey = 'booking-postcode:'.request()->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 15)) {
            $this->addError('postcode', 'Too many checks in a short time. Please wait a minute and try again.');

            return;
        }

        RateLimiter::hit($throttleKey);
        $answer = app(CoverageService::class)->check($this->postcode);

        if ($answer->status === CoverageStatus::Invalid) {
            if (! $quiet) {
                $this->addError('postcode', 'We could not find that postcode. Please check it and try again, e.g. BB2 4NY.');
            }

            return;
        }

        $this->postcode = (string) $answer->postcode;

        if (! $answer->isCovered() || $answer->area === null) {
            $this->outOfArea = true;
            $this->serviceAreaId = null;

            return;
        }

        if (Postcode::isDistrictOnly($this->postcode)) {
            $this->addError('postcode', "Good news, we collect from {$this->postcode}. Please enter your full postcode so we can book your collection.");

            return;
        }

        $this->serviceAreaId = $answer->area->getKey();
        $this->areaName = $answer->area->name;
        $this->collectionFeePence = $answer->area->collection_fee_pence;

        $this->advanceTo($this->model !== '' ? ($this->selectedRepairs !== [] ? 4 : 3) : 2);
    }

    /**
     * Out of area: save a lead asking us to get in touch (or arrange a post-in repair).
     */
    public function requestOutOfAreaHelp(CaptureLead $capture, AttributionTracker $attribution, BookingSettings $settings): void
    {
        $this->validate([
            'outOfAreaName' => ['required', 'string', 'max:160'],
            'outOfAreaPhone' => ['nullable', 'required_without:outOfAreaEmail', (new PhoneRule)->country('GB')],
            'outOfAreaEmail' => ['nullable', 'required_without:outOfAreaPhone', 'email:rfc', 'max:255'],
            'outOfAreaMessage' => ['nullable', 'string', 'max:2000'],
        ], [
            'outOfAreaName.required' => 'Please tell us your name.',
            'outOfAreaPhone.required_without' => 'Please give us a phone number or an email address.',
            'outOfAreaEmail.required_without' => 'Please give us a phone number or an email address.',
            'outOfAreaPhone.phone' => 'Please enter a valid UK phone number.',
            'outOfAreaEmail.email' => 'Please enter a valid email address.',
        ]);

        if (! $this->passesBookingSpamChecks('outOfAreaName')) {
            return;
        }

        $lead = $capture->handle(new LeadData(
            type: $settings->post_in_enabled ? LeadType::PostIn : LeadType::OutOfArea,
            name: $this->outOfAreaName,
            email: $this->outOfAreaEmail ?: null,
            phone: $this->outOfAreaPhone ?: null,
            postcode: $this->postcode,
            deviceModelId: $this->selectedModel?->getKey(),
            message: $this->outOfAreaMessage ?: null,
        ), $attribution->current(request()));

        $this->outOfAreaReference = $lead->reference;
        $this->dispatch('zr-track', name: 'generate_lead', params: ['event_id' => $lead->event_id, 'lead_type' => $lead->type->value]);
    }

    public function changePostcode(): void
    {
        $this->outOfArea = false;
        $this->outOfAreaReference = null;
        $this->serviceAreaId = null;
        $this->step = 1;
    }

    /**
     * Step 2: the customer's model.
     */
    public function chooseModel(string $slug): void
    {
        if (! $this->models->contains('slug', $slug)) {
            return;
        }

        if ($slug !== $this->model) {
            $this->model = $slug;
            $this->selectedRepairs = [];
            $this->qualities = [];
            unset($this->selectedModel, $this->prices);
        }

        // One tap is enough: picking a model moves straight on to the repairs.
        $this->submitDevice();
    }

    public function submitDevice(): void
    {
        $this->requireStep(2);

        $this->validate([
            'model' => ['required', 'string'],
            'colour' => ['nullable', 'string', 'max:40'],
            'storage' => ['nullable', 'string', 'max:20'],
        ], ['model.required' => 'Please choose your iPhone model.']);

        if ($this->selectedModel === null) {
            $this->addError('model', 'Please choose your iPhone model.');

            return;
        }

        $this->savePartial();
        $this->advanceTo(3);
    }

    /**
     * Keep the quality choice valid when a repair is ticked or unticked.
     */
    public function updatedSelectedRepairs(): void
    {
        $this->selectedRepairs = array_values(array_unique(array_map('strval', $this->selectedRepairs)));

        foreach ($this->selectedRepairs as $repairId) {
            $options = $this->prices->get((int) $repairId);

            if ($options === null) {
                continue;
            }

            $current = $this->qualities[$repairId] ?? null;

            if ($current === null || ! $options->contains(fn (RepairPrice $price): bool => (string) $price->part_quality_id === $current || ($current === '' && $price->part_quality_id === null))) {
                $this->qualities[$repairId] = (string) ($options->first()->part_quality_id ?? '');
            }
        }
    }

    /**
     * "Not sure what's wrong?" books a diagnostic check on its own.
     */
    public function chooseDiagnostics(): void
    {
        $diagnostics = $this->diagnosticsPrice();

        if ($diagnostics === null) {
            return;
        }

        $this->selectedRepairs = [(string) $diagnostics->repair_type_id];
        $this->qualities = [(string) $diagnostics->repair_type_id => (string) ($diagnostics->part_quality_id ?? '')];
        $this->submitRepairs();
    }

    public function submitRepairs(): void
    {
        $this->requireStep(3);

        if ($this->chosenPrices()->isEmpty()) {
            $this->addError('selectedRepairs', 'Please choose at least one repair, or "Not sure what’s wrong?".');

            return;
        }

        $this->savePartial();
        $this->advanceTo(4);
    }

    public function chooseCollectionDate(string $date): void
    {
        $dateHasAvailableSlots = collect($this->collectionSlots)
            ->contains(fn (CollectionSlot $slot): bool => $slot->date->toDateString() === $date);

        if (! $dateHasAvailableSlots) {
            return;
        }

        $this->selectedDate = $date;

        if ($this->slot !== '' && ! str_starts_with($this->slot, $date.':')) {
            $this->slot = '';
        }

        $this->resetErrorBag('slot');
    }

    public function updatedSlot(string $slotKey): void
    {
        if ($slotKey === '') {
            $this->selectedDate = '';

            return;
        }

        $slot = app(AvailabilityService::class)->findSlot($slotKey);

        if ($slot === null) {
            $this->slot = '';
            $this->addError('slot', 'Sorry, that time is no longer available. Please choose another.');
            unset($this->collectionSlots);

            return;
        }

        $this->slot = $slotKey;
        $this->selectedDate = $slot->date->toDateString();
        $this->resetErrorBag('slot');
    }

    /**
     * Step 4: collection slot and where to return the phone.
     */
    public function submitCollection(AvailabilityService $availability): void
    {
        $this->requireStep(4);

        $rules = [
            'slot' => ['required', 'string'],
            'returnTo' => ['required', 'in:same,different'],
        ];

        if ($this->returnTo === 'different') {
            $rules += [
                'returnLine1' => ['required', 'string', 'max:120'],
                'returnLine2' => ['nullable', 'string', 'max:120'],
                'returnTown' => ['required', 'string', 'max:80'],
                'returnPostcode' => ['required', 'string', 'max:10'],
            ];
        }

        $this->validate($rules, [
            'slot.required' => 'Please choose a collection time.',
            'returnLine1.required' => 'Please enter the first line of the return address.',
            'returnTown.required' => 'Please enter the town.',
            'returnPostcode.required' => 'Please enter the postcode.',
        ]);

        if ($this->returnTo === 'different') {
            $normalised = Postcode::normalise($this->returnPostcode);

            if ($normalised === null) {
                $this->addError('returnPostcode', 'Please enter a full UK postcode, e.g. BB2 4NY.');

                return;
            }

            $this->returnPostcode = $normalised;
        }

        if ($availability->findSlot($this->slot) === null) {
            $this->addError('slot', 'Sorry, that time is no longer available. Please choose another.');
            unset($this->collectionSlots);

            return;
        }

        $this->savePartial();
        $this->advanceTo(5);
    }

    /**
     * Save the booking so far as soon as we have a way to contact the customer.
     */
    public function updatedPhone(): void
    {
        $this->savePartialIfContactable();
    }

    public function updatedEmail(): void
    {
        $this->savePartialIfContactable();
    }

    /**
     * Step 5: contact details and consents.
     */
    public function submitDetails(): void
    {
        $this->requireStep(5);

        $this->validate([
            'firstName' => ['required', 'string', 'max:80'],
            'lastName' => ['required', 'string', 'max:80'],
            'phone' => ['required', (new PhoneRule)->country('GB')],
            'email' => ['required', 'email:rfc', 'max:255'],
            'line1' => ['required', 'string', 'max:120'],
            'line2' => ['nullable', 'string', 'max:120'],
            'town' => ['required', 'string', 'max:80'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'colour' => ['nullable', 'string', 'max:40'],
            'storage' => ['nullable', 'string', 'max:20'],
            'acceptTerms' => ['accepted'],
            'startStraightAway' => ['boolean'],
            'marketingConsent' => ['boolean'],
        ], [
            'firstName.required' => 'Please enter your first name.',
            'lastName.required' => 'Please enter your last name.',
            'phone.required' => 'Please enter your mobile number so the driver can reach you.',
            'phone.phone' => 'Please enter a valid UK phone number, e.g. 07360 127538.',
            'email.required' => 'Please enter your email address for your confirmation.',
            'email.email' => 'Please enter a valid email address.',
            'line1.required' => 'Please enter the first line of your address.',
            'town.required' => 'Please enter your town.',
            'acceptTerms.accepted' => 'Please confirm you have read and agree to the repair terms.',
        ]);

        $this->savePartial();
        $this->advanceTo(6);
    }

    /**
     * Step 6: book it.
     */
    public function confirm(CreateBooking $createBooking, AttributionTracker $attribution): void
    {
        $this->requireStep(6);

        if (! $this->passesBookingSpamChecks('confirm')) {
            return;
        }

        try {
            $job = $createBooking->handle(new BookingData(
                postcode: $this->postcode,
                serviceAreaId: (int) $this->serviceAreaId,
                deviceModelId: (int) $this->selectedModel?->getKey(),
                repairs: $this->repairChoices(),
                slotKey: $this->slot,
                firstName: trim($this->firstName),
                lastName: trim($this->lastName) ?: null,
                email: trim($this->email),
                phone: $this->phone,
                collectionAddress: ['line1' => trim($this->line1), 'line2' => trim($this->line2) ?: null, 'town' => trim($this->town), 'postcode' => $this->postcode],
                returnAddress: $this->returnTo === 'different'
                    ? ['line1' => trim($this->returnLine1), 'line2' => trim($this->returnLine2) ?: null, 'town' => trim($this->returnTown), 'postcode' => $this->returnPostcode]
                    : null,
                deviceColour: trim($this->colour) ?: null,
                deviceStorage: trim($this->storage) ?: null,
                notes: trim($this->notes) ?: null,
                marketingConsent: $this->marketingConsent,
                startWithinCancellationPeriod: $this->startStraightAway,
            ), $attribution->current(request()), $this->partialLead());
        } catch (ValidationException $exception) {
            $errors = $exception->errors();

            if (isset($errors['slot'])) {
                $this->slot = '';
                unset($this->collectionSlots);
                $this->step = 4;
            } elseif (isset($errors['repairs'])) {
                unset($this->prices);
                $this->step = 3;
            }

            $this->setErrorBag($errors);

            return;
        }

        $this->reset(
            'step', 'furthestStep', 'postcode', 'serviceAreaId', 'areaName', 'collectionFeePence', 'outOfArea',
            'outOfAreaName', 'outOfAreaPhone', 'outOfAreaEmail', 'outOfAreaMessage', 'outOfAreaReference',
            'model', 'colour', 'storage', 'selectedRepairs', 'qualities', 'slot', 'selectedDate', 'returnTo',
            'returnLine1', 'returnLine2', 'returnTown', 'returnPostcode', 'firstName', 'lastName', 'phone', 'email',
            'line1', 'line2', 'town', 'notes', 'acceptTerms', 'startStraightAway', 'marketingConsent', 'leadId',
        );

        $this->redirect(URL::signedRoute('booking.thanks', ['ref' => $job->reference]));
    }

    /**
     * Jump back to an earlier step to change something.
     */
    public function goToStep(int $step): void
    {
        if ($step >= 1 && $step <= $this->furthestStep && ($step === 1 || $this->serviceAreaId !== null)) {
            $this->step = $step;
        }
    }

    public function back(): void
    {
        $this->goToStep(max(1, $this->step - 1));
    }

    /**
     * @return Collection<int, DeviceModel>
     */
    #[Computed]
    public function models(): Collection
    {
        return app(PricingService::class)->modelsWithPrices();
    }

    #[Computed]
    public function selectedModel(): ?DeviceModel
    {
        return $this->model === '' ? null : $this->models->firstWhere('slug', $this->model);
    }

    /**
     * Prices for the chosen model, grouped by repair type, diagnostics excluded
     * (it has its own "Not sure what's wrong?" button).
     *
     * @return SupportCollection<int, Collection<int, RepairPrice>>
     */
    #[Computed]
    public function prices(): SupportCollection
    {
        $model = $this->selectedModel;

        return $model === null ? collect() : app(PricingService::class)->pricesForModel($model);
    }

    /**
     * @return list<CollectionSlot>
     */
    #[Computed]
    public function collectionSlots(): array
    {
        return app(AvailabilityService::class)->upcomingSlots();
    }

    /**
     * @return list<array{label: string, quality: string|null}>
     */
    #[Computed]
    public function summaryLines(): array
    {
        return $this->chosenPrices()->map(fn (RepairPrice $price): array => [
            'label' => $price->repairType->name,
            'quality' => $price->partQuality?->name,
        ])->values()->all();
    }

    public function repairsTotalPence(): int
    {
        return $this->chosenPrices()->sum(fn (RepairPrice $price): int => $price->effectivePricePence());
    }

    public function render(BookingSettings $settings): View
    {
        $slot = $this->slot !== '' ? collect($this->collectionSlots)->first(fn (CollectionSlot $candidate): bool => $candidate->key() === $this->slot) : null;

        return view('livewire.booking-wizard', [
            'stepLabels' => self::STEP_LABELS,
            'hasDiagnostics' => $this->diagnosticsPrice() !== null,
            'slotsByDate' => collect($this->collectionSlots)->groupBy(fn (CollectionSlot $candidate): string => $candidate->date->toDateString()),
            'chosenSlot' => $slot,
            'popularModels' => $this->models->where('is_popular', true)->take(8),
            'settings' => $settings,
        ]);
    }

    /**
     * The live price for each chosen repair and quality.
     *
     * @return SupportCollection<int, RepairPrice>
     */
    private function chosenPrices(): SupportCollection
    {
        $chosen = collect();

        foreach ($this->selectedRepairs as $repairId) {
            $options = $this->prices->get((int) $repairId);
            $quality = $this->qualities[$repairId] ?? '';

            $price = $options?->first(fn (RepairPrice $option): bool => (string) ($option->part_quality_id ?? '') === $quality);

            if ($price !== null) {
                $chosen->push($price);
            }
        }

        return $chosen;
    }

    private function diagnosticsPrice(): ?RepairPrice
    {
        return $this->prices
            ->flatten()
            ->first(fn (RepairPrice $price): bool => $price->repairType->slug === RepairType::DIAGNOSTICS);
    }

    /**
     * @return list<array{repair_type_id: int, part_quality_id: int|null}>
     */
    private function repairChoices(): array
    {
        return $this->chosenPrices()->map(fn (RepairPrice $price): array => [
            'repair_type_id' => $price->repair_type_id,
            'part_quality_id' => $price->part_quality_id,
        ])->values()->all();
    }

    private function preselectRepair(string $repairSlug, string $qualitySlug): void
    {
        $options = $this->prices->first(fn (Collection $group): bool => $group->first()?->repairType->slug === $repairSlug);

        if ($options === null) {
            return;
        }

        $qualityId = $qualitySlug !== '' ? PartQuality::query()->where('slug', $qualitySlug)->value('id') : null;
        $price = $options->first(fn (RepairPrice $option): bool => $qualityId !== null && $option->part_quality_id === $qualityId) ?? $options->first();

        $this->selectedRepairs = [(string) $price->repair_type_id];
        $this->qualities = [(string) $price->repair_type_id => (string) ($price->part_quality_id ?? '')];
    }

    private function advanceTo(int $step): void
    {
        $this->step = $step;
        $this->furthestStep = max($this->furthestStep, $step);
    }

    /**
     * Steps must be completed in order; a tampered request jumps back.
     */
    private function requireStep(int $step): void
    {
        $ready = match (true) {
            $step >= 2 && $this->serviceAreaId === null => 1,
            $step >= 3 && $this->selectedModel === null => 2,
            $step >= 4 && $this->chosenPrices()->isEmpty() => 3,
            $step >= 5 && $this->slot === '' => 4,
            $step >= 6 && (! $this->acceptTerms || $this->email === '' || $this->phone === '') => 5,
            default => null,
        };

        if ($ready !== null) {
            $this->step = $ready;

            throw ValidationException::withMessages(['form' => 'Please complete this step first.']);
        }
    }

    private function passesBookingSpamChecks(string $errorField): bool
    {
        return $this->passesSpamChecks('booking', maxAttempts: 5, decaySeconds: 600, minimumSeconds: 5, errorField: $errorField);
    }

    private function savePartialIfContactable(): void
    {
        $this->resetErrorBag(['phone', 'email']);

        $validPhone = $this->phone !== '' && validator(['phone' => $this->phone], ['phone' => [(new PhoneRule)->country('GB')]])->passes();
        $validEmail = $this->email !== '' && validator(['email' => $this->email], ['email' => ['email:rfc', 'max:255']])->passes();

        if ($validPhone || $validEmail) {
            $this->savePartial(force: true);
        }
    }

    /**
     * Keep the lead up to date with the choices so far. Creates it only once
     * the customer has given a phone or email.
     */
    private function savePartial(bool $force = false): void
    {
        if ($this->leadId === null && ! $force) {
            return;
        }

        $prices = $this->chosenPrices();
        $model = $this->selectedModel;
        $phoneIsValid = $this->phone !== '' && validator(['phone' => $this->phone], ['phone' => [(new PhoneRule)->country('GB')]])->passes();
        $emailIsValid = $this->email !== '' && validator(['email' => $this->email], ['email' => ['email:rfc']])->passes();

        $lead = app(SavePartialBooking::class)->handle($this->partialLead(), new LeadData(
            type: LeadType::Booking,
            name: trim($this->firstName.' '.$this->lastName) ?: null,
            email: $emailIsValid ? trim($this->email) : null,
            phone: $phoneIsValid ? $this->phone : null,
            postcode: $this->postcode ?: null,
            deviceModelId: $model?->getKey(),
            repairTypeId: $prices->first()?->repair_type_id,
            serviceAreaId: $this->serviceAreaId,
            details: [
                'step' => self::STEP_LABELS[$this->step] ?? null,
                'repairs' => $prices->map(fn (RepairPrice $price): string => $price->repairType->name.($price->partQuality ? ' ('.$price->partQuality->name.')' : ''))->values()->all(),
                'slot' => $this->slot ?: null,
            ],
            estimatedValuePence: $prices->isEmpty() ? null : $this->repairsTotalPence() + $this->collectionFeePence,
        ), app(AttributionTracker::class)->current(request()));

        $this->leadId = $lead->getKey();
    }

    private function partialLead(): ?Lead
    {
        return $this->leadId !== null ? Lead::query()->find($this->leadId) : null;
    }
}
