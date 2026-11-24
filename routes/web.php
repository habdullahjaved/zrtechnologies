<?php

use App\Http\Controllers\AreasController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\RepairPricesController;
use App\Http\Controllers\RobotsController;
use App\Support\Seo\SeoData;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/repair-prices', RepairPricesController::class)->name('prices');
Route::get('/areas-we-cover', AreasController::class)->name('areas.index');

Route::get('/robots.txt', RobotsController::class)->name('robots');

/*
| Design-system reference for reviewing components. Local development only.
*/
if (app()->isLocal()) {
    Route::get('/styleguide', fn () => view('pages.styleguide', [
        'seo' => new SeoData(title: 'Style guide', noindex: true),
    ]))->name('styleguide');
}
