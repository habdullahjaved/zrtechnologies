<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

/**
 * Send visitors from the retired price list to the quote request form.
 */
class RepairPricesController extends Controller
{
    public function __invoke(): RedirectResponse
    {
        return redirect()->route('booking.create', ['form' => 'quote'], 301);
    }
}
