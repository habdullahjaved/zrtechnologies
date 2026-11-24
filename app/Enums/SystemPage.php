<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Pages the website always has. Each has a fixed address and a row in the
 * pages table holding its editable heading, introduction, blocks and SEO.
 *
 * Content pages (about, warranty, legal) are rendered entirely from their
 * blocks; the others have their own template, with the page's blocks shown
 * as extra content.
 */
enum SystemPage: string implements HasLabel
{
    case Home = 'home';
    case IphoneRepair = 'iphone-repair';
    case Prices = 'prices';
    case HowItWorks = 'how-it-works';
    case Areas = 'areas';
    case Booking = 'booking';
    case TrackRepair = 'track-repair';
    case About = 'about';
    case Contact = 'contact';
    case Reviews = 'reviews';
    case Faqs = 'faqs';
    case Warranty = 'warranty';
    case Business = 'business';
    case Blog = 'blog';
    case Videos = 'videos';
    case Privacy = 'privacy-policy';
    case Cookies = 'cookie-policy';
    case Terms = 'terms-and-conditions';
    case RepairTerms = 'repair-terms';
    case Accessibility = 'accessibility';

    public function getLabel(): string
    {
        return match ($this) {
            self::Home => 'Home',
            self::IphoneRepair => 'iPhone repair',
            self::Prices => 'Repair prices',
            self::HowItWorks => 'How it works',
            self::Areas => 'Areas we cover',
            self::Booking => 'Book a repair',
            self::TrackRepair => 'Track a repair',
            self::About => 'About us',
            self::Contact => 'Contact',
            self::Reviews => 'Reviews',
            self::Faqs => 'FAQs',
            self::Warranty => 'Warranty',
            self::Business => 'Business repairs',
            self::Blog => 'Blog',
            self::Videos => 'Videos',
            self::Privacy => 'Privacy policy',
            self::Cookies => 'Cookie policy',
            self::Terms => 'Terms and conditions',
            self::RepairTerms => 'Repair terms',
            self::Accessibility => 'Accessibility statement',
        };
    }

    public function routeName(): string
    {
        return match ($this) {
            self::IphoneRepair => 'repairs.index',
            self::Areas => 'areas.index',
            self::Booking => 'booking.create',
            self::Blog => 'blog.index',
            self::Videos => 'videos.index',
            self::Privacy => 'legal.privacy',
            self::Cookies => 'legal.cookies',
            self::Terms => 'legal.terms',
            self::RepairTerms => 'legal.repair-terms',
            self::Accessibility => 'legal.accessibility',
            default => $this->value,
        };
    }

    /**
     * The page's address (without the leading slash).
     */
    public function slug(): string
    {
        return match ($this) {
            self::Prices => 'repair-prices',
            self::Areas => 'areas-we-cover',
            self::Booking => 'book-repair',
            default => $this->value,
        };
    }

    /**
     * Pages rendered entirely from their content blocks by the page controller.
     */
    public function isContentPage(): bool
    {
        return in_array($this, self::contentPages(), true);
    }

    public function isLegal(): bool
    {
        return in_array($this, [self::Privacy, self::Cookies, self::Terms, self::RepairTerms, self::Accessibility], true);
    }

    /**
     * @return list<self>
     */
    public static function contentPages(): array
    {
        return [self::About, self::Warranty, self::Privacy, self::Cookies, self::Terms, self::RepairTerms, self::Accessibility];
    }
}
