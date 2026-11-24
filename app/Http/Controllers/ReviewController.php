<?php

namespace App\Http\Controllers;

use App\Enums\SystemPage;
use App\Models\Page;
use App\Models\Review;
use App\Services\Seo\SeoResolver;
use App\Settings\SocialSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * /reviews: genuine customer reviews (never samples in production), and
 * /reviews/leave, a short link to our Google review form.
 */
class ReviewController extends Controller
{
    public function index(SeoResolver $seo, SocialSettings $social): View
    {
        $page = Page::system(SystemPage::Reviews);
        $breadcrumbs = [['label' => 'Reviews']];
        $reviews = Review::query()->displayable()->paginate(12);

        return view('pages.reviews', [
            'seo' => $seo->forPage($page, $breadcrumbs),
            'page' => $page,
            'breadcrumbs' => $breadcrumbs,
            'reviews' => $reviews,
            'averageRating' => $reviews->total() > 0 ? round((float) Review::query()->displayable()->avg('rating'), 1) : null,
            'reviewUrl' => filled($social->google_review_url) ? route('reviews.leave') : null,
            'googleProfileUrl' => $social->google_business_profile,
        ]);
    }

    /**
     * A stable link for emails, QR codes and WhatsApp messages. Goes to the
     * Google review form once its link is set, otherwise to the contact page.
     */
    public function leave(SocialSettings $social): RedirectResponse
    {
        return filled($social->google_review_url)
            ? redirect()->away($social->google_review_url)
            : redirect()->route('contact');
    }
}
