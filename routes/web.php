<?php

use App\Enums\SystemPage;
use App\Http\Controllers\AreasController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\BusinessController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\HowItWorksController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\RepairController;
use App\Http\Controllers\RepairPricesController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\RobotsController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\TrackRepairController;
use App\Http\Controllers\VideoController;
use App\Support\Seo\SeoData;
use Illuminate\Support\Facades\Route;

/*
| Public pages, served from the full-page cache to anonymous visitors.
*/
Route::middleware('page-cache')->group(function () {
    Route::get('/', HomeController::class)->name('home');

    Route::get('/iphone-repair', [RepairController::class, 'index'])->name('repairs.index');
    Route::get('/iphone-repair/{slug}', [RepairController::class, 'show'])->name('repairs.show');
    Route::get('/iphone-repair/{model}/{repair}', [RepairController::class, 'modelRepair'])->name('repairs.model-repair');
    Route::get('/iphone-repair-{slug}', LocationController::class)->where('slug', '[a-z0-9-]+')->name('locations.show');

    Route::get('/repair-prices', RepairPricesController::class)->name('prices');
    Route::get('/areas-we-cover', AreasController::class)->name('areas.index');
    Route::get('/how-it-works', HowItWorksController::class)->name('how-it-works');
    Route::get('/faqs', FaqController::class)->name('faqs');
    Route::get('/reviews', [ReviewController::class, 'index'])->name('reviews');
    Route::get('/contact', ContactController::class)->name('contact');
    Route::get('/business', BusinessController::class)->name('business');

    Route::get('/videos', [VideoController::class, 'index'])->name('videos.index');
    Route::get('/videos/{slug}', [VideoController::class, 'show'])->name('videos.show');

    Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
    Route::get('/blog/feed', [BlogController::class, 'feed'])->name('blog.feed');
    Route::get('/blog/author/{slug}', [BlogController::class, 'author'])->name('blog.author');
    Route::get('/blog/tag/{slug}', [BlogController::class, 'tag'])->name('blog.tag');
    Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('blog.show');

    // About, warranty and the legal pages are built from their content blocks.
    foreach (SystemPage::contentPages() as $page) {
        Route::get('/'.$page->slug(), [PageController::class, 'system'])
            ->defaults('key', $page->value)
            ->name($page->routeName());
    }
});

/*
| Customer journeys and private pages: never cached.
*/
Route::get('/book-repair', [BookingController::class, 'create'])->name('booking.create');
Route::get('/book-repair/thank-you', [BookingController::class, 'thanks'])->middleware('signed')->name('booking.thanks');
Route::get('/book-repair/calendar/{reference}.ics', [BookingController::class, 'calendar'])->middleware('signed')->name('booking.calendar');

Route::get('/track-repair', [TrackRepairController::class, 'index'])->name('track-repair');
Route::get('/track-repair/{reference}', [TrackRepairController::class, 'show'])->middleware(['signed', 'throttle:30,1'])->name('track-repair.show');

Route::get('/reviews/leave', [ReviewController::class, 'leave'])->name('reviews.leave');

/*
| Machine endpoints.
*/
Route::get('/robots.txt', RobotsController::class)->name('robots');
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/sitemaps/{type}.xml', [SitemapController::class, 'show'])->name('sitemaps.show');

/*
| Design-system reference for reviewing components. Local development only.
*/
if (app()->isLocal()) {
    Route::get('/styleguide', fn () => view('pages.styleguide', [
        'seo' => new SeoData(title: 'Style guide', noindex: true),
    ]))->name('styleguide');
}

/*
| Custom pages created in the admin (/{slug}). Registered as the fallback, so
| it only runs when no other route (including the admin) matches.
*/
Route::fallback([PageController::class, 'show'])->middleware('page-cache')->name('page.show');
