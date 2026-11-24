<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\RobotsController;
use App\Support\Seo\SeoData;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/robots.txt', RobotsController::class)->name('robots');

/*
| Design-system reference for reviewing components. Local development only.
*/
if (app()->isLocal()) {
    Route::get('/styleguide', fn () => view('pages.styleguide', [
        'seo' => new SeoData(title: 'Style guide', noindex: true),
    ]))->name('styleguide');
}
