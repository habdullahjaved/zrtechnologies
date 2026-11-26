<?php

namespace App\Livewire;

use App\Actions\Leads\CaptureLead;
use App\Data\LeadData;
use App\Enums\LeadType;
use App\Livewire\Concerns\ProtectsAgainstSpam;
use App\Models\DeviceModel;
use App\Models\RepairType;
use App\Services\Attribution\AttributionTracker;
use App\Support\Postcode;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Propaganistas\LaravelPhone\Rules\Phone as PhoneRule;

/**
 * The lighter ways to get in touch (brief §5): quick quote, callback,
 * contact and business enquiry. Each one is saved as a tracked lead.
 *
 * @property-read Collection<int, DeviceModel> $models
 * @property-read Collection<int, RepairType> $repairTypes
 */
class EnquiryForm extends Component
{
    use ProtectsAgainstSpam;

    /** @var list<string> */
    public const array TYPES = ['quote', 'callback', 'contact', 'business'];

    /** @var array<string, string> */
    public const array CALLBACK_TIMES = [
        'any' => 'Any time',
        'morning' => 'Morning (9am–12pm)',
        'afternoon' => 'Afternoon (12pm–5pm)',
        'evening' => 'Early evening (5pm–7pm)',
    ];

    /** @var array<string, string> */
    public const array FLEET_SIZES = [
        '1-5' => '1 to 5 devices',
        '6-20' => '6 to 20 devices',
        '21-50' => '21 to 50 devices',
        '50+' => 'More than 50 devices',
    ];

    #[Locked]
    public string $type = 'contact';

    #[Locked]
    public string $idPrefix = 'enquiry';

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $postcode = '';

    public string $companyName = '';

    public string $model = '';

    public string $repair = '';

    public string $callbackTime = 'any';

    public string $fleetSize = '';

    public string $message = '';

    public bool $marketingConsent = false;

    #[Locked]
    public ?string $reference = null;

    public function mount(string $type = 'contact', ?string $idPrefix = null): void
    {
        $this->type = in_array($type, self::TYPES, true) ? $type : 'contact';
        $this->idPrefix = $idPrefix ?? 'enquiry-'.$this->type;
        $model = request()->query('model');
        $repair = request()->query('repair');
        $postcode = request()->query('postcode');
        $this->model = is_string($model) ? $model : '';
        $this->repair = is_string($repair) ? $repair : '';
        $this->postcode = is_string($postcode) ? $postcode : '';
    }

    public function submit(CaptureLead $capture, AttributionTracker $attribution): void
    {
        $this->validate($this->rules(), $this->messages());

        if (! $this->passesSpamChecks('enquiry', maxAttempts: 5, decaySeconds: 600)) {
            return;
        }

        $model = $this->model !== '' ? $this->models->firstWhere('slug', $this->model) : null;
        $repair = $this->repair !== '' ? $this->repairTypes->firstWhere('slug', $this->repair) : null;

        $lead = $capture->handle(new LeadData(
            type: $this->leadType(),
            name: trim($this->name),
            email: trim($this->email) ?: null,
            phone: trim($this->phone) ?: null,
            postcode: $this->postcode !== '' ? (Postcode::normalise($this->postcode) ?? mb_strtoupper(trim($this->postcode))) : null,
            companyName: trim($this->companyName) ?: null,
            deviceModelId: $model?->getKey(),
            repairTypeId: $repair?->getKey(),
            message: trim($this->message) ?: null,
            details: array_filter([
                'callback_time' => $this->type === 'callback' ? self::CALLBACK_TIMES[$this->callbackTime] ?? null : null,
                'fleet_size' => $this->type === 'business' ? self::FLEET_SIZES[$this->fleetSize] ?? null : null,
            ]),
            marketingConsent: $this->type !== 'callback' && $this->marketingConsent,
        ), $attribution->current(request()));

        $this->reference = $lead->reference;

        // Browser conversion, sharing the lead's event ID with the server-side send.
        $this->dispatch('zr-track', name: 'generate_lead', params: array_filter([
            'event_id' => $lead->event_id,
            'lead_type' => $lead->type->value,
            'user_data' => array_filter(['email' => $lead->email, 'phone_number' => $lead->phone]) ?: null,
        ]));

        $this->reset('name', 'email', 'phone', 'postcode', 'companyName', 'model', 'repair', 'message', 'marketingConsent', 'fleetSize');
    }

    public function sendAnother(): void
    {
        $this->reference = null;
    }

    /**
     * @return Collection<int, DeviceModel>
     */
    #[Computed]
    public function models(): Collection
    {
        return DeviceModel::query()->listed()->get();
    }

    /**
     * @return Collection<int, RepairType>
     */
    #[Computed]
    public function repairTypes(): Collection
    {
        return RepairType::query()->listed()->get();
    }

    public function render(): View
    {
        return view('livewire.enquiry-form', [
            'callbackTimes' => self::CALLBACK_TIMES,
            'fleetSizes' => self::FLEET_SIZES,
            'submitLabel' => match ($this->type) {
                'quote' => 'Request a quote',
                'callback' => 'Request a callback',
                'business' => 'Send enquiry',
                default => 'Send message',
            },
        ]);
    }

    private function leadType(): LeadType
    {
        return match ($this->type) {
            'quote' => LeadType::Quote,
            'callback' => LeadType::Callback,
            'business' => LeadType::Business,
            default => LeadType::Contact,
        };
    }

    /**
     * @return array<string, list<mixed>>
     */
    protected function rules(): array
    {
        $phone = (new PhoneRule)->country('GB');

        return match ($this->type) {
            'quote' => [
                'name' => ['required', 'string', 'max:160'],
                'phone' => ['required', $phone],
                'email' => ['nullable', 'email:rfc', 'max:255'],
                'model' => ['required', 'string', Rule::in($this->models->pluck('slug')->all())],
                'repair' => ['required', 'string', Rule::in($this->repairTypes->pluck('slug')->all())],
                'postcode' => ['required', 'string', 'max:10', function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! is_string($value) || Postcode::normalise($value) === null) {
                        $fail('Please enter a full UK postcode, e.g. BB2 4NY.');
                    }
                }],
                'message' => ['required', 'string', 'max:2000'],
            ],
            'callback' => [
                'name' => ['required', 'string', 'max:160'],
                'phone' => ['required', $phone],
                'callbackTime' => ['required', 'in:'.implode(',', array_keys(self::CALLBACK_TIMES))],
                'message' => ['nullable', 'string', 'max:1000'],
            ],
            'business' => [
                'companyName' => ['required', 'string', 'max:120'],
                'name' => ['required', 'string', 'max:160'],
                'email' => ['required', 'email:rfc', 'max:255'],
                'phone' => ['nullable', $phone],
                'fleetSize' => ['nullable', 'in:'.implode(',', array_keys(self::FLEET_SIZES))],
                'message' => ['required', 'string', 'max:3000'],
            ],
            default => [
                'name' => ['required', 'string', 'max:160'],
                'email' => ['required', 'email:rfc', 'max:255'],
                'phone' => ['nullable', $phone],
                'message' => ['required', 'string', 'max:3000'],
            ],
        };
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'name.required' => 'Please tell us your name.',
            'email.required' => 'Please enter your email address.',
            'email.required_without' => 'Please give us an email address or a phone number.',
            'phone.required' => 'Please enter the number we should call.',
            'phone.required_without' => 'Please give us a phone number or an email address.',
            'phone.phone' => 'Please enter a valid UK phone number, e.g. 07360 127538.',
            'email.email' => 'Please enter a valid email address.',
            'model.required' => 'Please choose your iPhone model.',
            'repair.required' => 'Please choose the service you need.',
            'postcode.required' => 'Please enter your postcode.',
            'companyName.required' => 'Please enter your organisation’s name.',
            'message.required' => 'Please tell us how we can help or describe the repair.',
        ];
    }
}
