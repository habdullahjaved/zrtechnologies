<?php

namespace App\Filament\Resources;

use App\Actions\Customers\FindOrCreateCustomer;
use App\Filament\Resources\Customers\CustomerResource;
use App\Models\Customer;
use App\Support\Money;
use App\Support\Phone;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use Propaganistas\LaravelPhone\Rules\Phone as PhoneRule;

/**
 * Form fields shared by the CRM and workshop screens.
 */
final class CrmFields
{
    /**
     * Money typed in pounds ("89.00"), stored in pence.
     */
    public static function pounds(string $attribute, ?string $label = null): TextInput
    {
        return TextInput::make($attribute)
            ->label($label)
            ->prefix('£')
            ->numeric()
            ->minValue(0)
            ->maxValue(20000)
            ->step(0.01)
            ->formatStateUsing(fn (mixed $state): ?string => filled($state) ? Money::toPounds((int) $state) : null)
            ->dehydrateStateUsing(fn (mixed $state): ?int => filled($state) ? Money::parsePounds($state) : null);
    }

    /**
     * A UK phone number, shown as 07360 127538 and stored as +447360127538.
     */
    public static function phone(string $attribute = 'phone', string $label = 'Mobile number'): TextInput
    {
        return TextInput::make($attribute)
            ->label($label)
            ->tel()
            ->maxLength(20)
            ->rule((new PhoneRule)->country(Phone::COUNTRY))
            ->formatStateUsing(fn (?string $state): ?string => filled($state) ? Phone::display($state) : null)
            ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? Phone::toE164($state) : null);
    }

    /**
     * A warning under an email or phone field when another customer already uses it.
     */
    public static function duplicateCustomerHint(?string $value, string $field, ?Model $record): ?HtmlString
    {
        if (blank($value)) {
            return null;
        }

        $finder = app(FindOrCreateCustomer::class);
        $existing = $field === 'email' ? $finder->find($value, null) : $finder->find(null, $value);

        if (! $existing instanceof Customer || ($record instanceof Customer && $existing->is($record))) {
            return null;
        }

        $url = CustomerResource::getUrl('edit', ['record' => $existing]);

        return new HtmlString('Already used by <a href="'.e($url).'" class="font-semibold underline">'.e($existing->full_name).'</a>. Check before creating a duplicate.');
    }
}
