<?php

namespace App\Actions\Customers;

use App\Models\Customer;
use App\Support\Phone;

/**
 * Finds the customer by mobile, then by email, so repeat customers keep one
 * record. Blank details on an existing record are filled in; details already
 * held are not overwritten by what someone types into a public form.
 */
final class FindOrCreateCustomer
{
    public function handle(string $firstName, ?string $lastName = null, ?string $email = null, ?string $phone = null): Customer
    {
        $customer = $this->find($email, $phone);

        if ($customer === null) {
            return Customer::query()->create([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'phone' => $phone,
            ]);
        }

        $customer->fill(array_filter([
            'last_name' => blank($customer->last_name) ? $lastName : null,
            'email' => blank($customer->email) ? $email : null,
            'phone' => blank($customer->phone) ? $phone : null,
        ], filled(...)));

        $customer->save();

        return $customer;
    }

    /**
     * Existing customers sharing this mobile or email (duplicate detection).
     */
    public function find(?string $email, ?string $phone): ?Customer
    {
        if (filled($phone)) {
            $customer = Customer::query()->where('phone', Phone::toE164($phone))->oldest('id')->first();

            if ($customer !== null) {
                return $customer;
            }
        }

        if (filled($email)) {
            return Customer::query()->where('email', mb_strtolower(trim($email)))->oldest('id')->first();
        }

        return null;
    }
}
