<?php

use App\Http\Controllers\AreasController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\BusinessController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\RepairPricesController;
use App\Http\Controllers\RobotsController;
use App\Http\Controllers\TrackRepairController;
use App\Support\Seo\SeoData;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/repair-prices', RepairPricesController::class)->name('prices');
Route::get('/areas-we-cover', AreasController::class)->name('areas.index');

Route::get('/book-repair', [BookingController::class, 'create'])->name('booking.create');
Route::get('/book-repair/thank-you', [BookingController::class, 'thanks'])->middleware('signed')->name('booking.thanks');
Route::get('/book-repair/calendar/{reference}.ics', [BookingController::class, 'calendar'])->middleware('signed')->name('booking.calendar');

Route::get('/track-repair', [TrackRepairController::class, 'index'])->name('track-repair');
Route::get('/track-repair/{reference}', [TrackRepairController::class, 'show'])->middleware(['signed', 'throttle:30,1'])->name('track-repair.show');

Route::get('/contact', ContactController::class)->name('contact');
Route::get('/business', BusinessController::class)->name('business');

Route::get('/robots.txt', RobotsController::class)->name('robots');

/*
| Design-system reference for reviewing components. Local development only.
*/
if (app()->isLocal()) {
    Route::get('/styleguide', fn () => view('pages.styleguide', [
        'seo' => new SeoData(title: 'Style guide', noindex: true),
    ]))->name('styleguide');
}
