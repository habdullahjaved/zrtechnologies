<?php

namespace App\Support\Content;

use App\Enums\PublishStatus;
use App\Enums\SystemPage;
use App\Models\Page;
use App\Models\SeoMeta;

/**
 * The starting wording for every system page: heading (H1), eyebrow,
 * introduction, SEO title, meta description and share image.
 *
 * The content seeder creates the page rows from these, and pages fall back to
 * them if a row is missing, so a page never renders without a heading or title.
 */
final class SystemPageDefaults
{
    /**
     * @var array<string, array{title: string, eyebrow?: string, intro?: string, meta_title?: string, meta_description?: string, og_image?: string}>
     */
    private const array PAGES = [
        'home' => [
            'title' => 'Expert iPhone Repair in Blackburn',
            'eyebrow' => 'We Collect, Repair & Return',
            'intro' => 'No trips, no queues. Book online, we pick up your iPhone from home or work, repair it with quality parts and bring it straight back, with a warranty on every repair.',
            'og_image' => 'images/og/og-home.jpg',
        ],
        'iphone-repair' => [
            'title' => 'iPhone Repair in Blackburn',
            'eyebrow' => 'Collection and delivery repair',
            'intro' => 'Screens, batteries, charging ports, cameras and more, repaired with quality parts and returned to your door. Clear prices for every model and a warranty on every repair.',
            'meta_title' => 'iPhone Repair in Blackburn & East Lancashire',
            'meta_description' => 'iPhone repair in Blackburn without leaving home. We collect, repair and return your iPhone: screens, batteries, charging ports, cameras and more, with a warranty.',
            'og_image' => 'images/og/og-iphone-repair.jpg',
        ],
        'prices' => [
            'title' => 'iPhone Repair Prices',
            'eyebrow' => 'Clear prices for every model',
            'intro' => 'Choose your iPhone to see the price of every repair. The price you see is the price you pay, and if anything changes after diagnosis we ask you first.',
            'meta_title' => 'iPhone Repair Prices in Blackburn',
            'meta_description' => 'Clear, fixed iPhone repair prices for every model, from screens and batteries to charging ports and cameras. Collection and return across Blackburn.',
            'og_image' => 'images/og/og-repair-prices.jpg',
        ],
        'how-it-works' => [
            'title' => 'How Our Collection and Repair Service Works',
            'eyebrow' => 'Four simple steps',
            'intro' => 'Book online in a couple of minutes, we collect your iPhone from your door, repair it with quality parts and bring it back. Here is exactly what happens, and how long each step takes.',
            'meta_title' => 'How It Works | iPhone Collection & Repair',
            'meta_description' => 'Book online, we collect your iPhone, repair it and return it to your door. See each step of our collection and delivery repair service and how long it takes.',
            'og_image' => 'images/og/og-how-it-works.jpg',
        ],
        'areas' => [
            'title' => 'Areas We Cover',
            'eyebrow' => 'Collection across East Lancashire',
            'intro' => 'We collect your iPhone from your home or workplace, repair it and bring it back. Check your postcode or find your town below.',
            'meta_title' => 'Areas We Cover for iPhone Collection & Repair',
            'meta_description' => 'We collect and return iPhones across Blackburn, Darwen, Accrington, Great Harwood and Clitheroe. Check your postcode to see if we collect from you.',
            'og_image' => 'images/og/og-areas-we-cover.jpg',
        ],
        'booking' => [
            'title' => 'Book Your iPhone Repair',
            'eyebrow' => 'Takes about two minutes',
            'intro' => 'Tell us where you are, what needs fixing and when suits you. It takes about two minutes, and you pay nothing until your iPhone comes back.',
            'meta_title' => 'Book an iPhone Repair with Collection',
            'meta_description' => 'Book your iPhone repair online in about two minutes. Choose a collection slot and we will collect, repair and return your iPhone. Nothing to pay today.',
            'og_image' => 'images/og/og-book-repair.jpg',
        ],
        'track-repair' => [
            'title' => 'Track Your Repair',
            'eyebrow' => 'Repair status',
            'intro' => 'Enter the reference from your confirmation email and the postcode we collected from.',
            'meta_title' => 'Track Your iPhone Repair',
            'meta_description' => 'Check the status of your iPhone repair with your booking reference and postcode.',
            'og_image' => 'images/og/og-track-repair.jpg',
        ],
        'about' => [
            'title' => 'About ZR Technologies',
            'eyebrow' => 'Local, independent, on your side',
            'intro' => 'We are a Blackburn company that repairs iPhones without making you travel. We collect your phone, repair it with care and bring it back.',
            'meta_title' => 'About Us | Local iPhone Repair Specialists',
            'meta_description' => 'ZR Technologies is a Blackburn company offering iPhone repair with collection and delivery. Meet the team and find out how we work.',
            'og_image' => 'images/og/og-about.jpg',
        ],
        'contact' => [
            'title' => 'Contact Us',
            'eyebrow' => 'We are here to help',
            'intro' => 'Call, WhatsApp or send us a message. Ask for a quote or a callback and a member of the team will get back to you, usually within a few hours. We are open 24/7.',
            'meta_title' => 'Contact Us',
            'meta_description' => 'Contact ZR Technologies about an iPhone repair in Blackburn. Call or WhatsApp 07360 127538, or send us a message.',
            'og_image' => 'images/og/og-contact.jpg',
        ],
        'reviews' => [
            'title' => 'Customer Reviews',
            'eyebrow' => 'What our customers say',
            'intro' => 'Every review here was left by a real customer. If we have repaired your iPhone, we would love to hear how it went.',
            'meta_title' => 'Customer Reviews | iPhone Repair Blackburn',
            'meta_description' => 'Read genuine reviews from customers who used our iPhone collection and repair service in Blackburn and East Lancashire.',
        ],
        'faqs' => [
            'title' => 'Frequently Asked Questions',
            'eyebrow' => 'Help and answers',
            'intro' => 'Everything you need to know about booking, collection, repairs, prices, warranty and your data.',
            'meta_title' => 'iPhone Repair FAQs',
            'meta_description' => 'Answers to common questions about our iPhone collection and repair service: booking, prices, parts, warranty, data and passcodes.',
            'og_image' => 'images/og/og-faqs.jpg',
        ],
        'warranty' => [
            'title' => 'Our Repair Warranty',
            'eyebrow' => 'Peace of mind',
            'intro' => 'Every repair comes with a warranty on the part we fit and our workmanship. Here is what is covered, for how long, and how to make a claim.',
            'meta_title' => 'iPhone Repair Warranty',
            'meta_description' => 'Every ZR Technologies iPhone repair includes a warranty on parts and workmanship. Find out what is covered, how long it lasts and how to claim.',
            'og_image' => 'images/og/og-warranty.jpg',
        ],
        'business' => [
            'title' => 'iPhone Repairs for Business',
            'eyebrow' => 'Businesses, schools and organisations',
            'intro' => 'Keep your team’s iPhones working. We collect from your site, repair the devices and bring them back, with one point of contact for every job.',
            'meta_title' => 'Business iPhone Repairs',
            'meta_description' => 'iPhone repairs for businesses, schools and organisations in East Lancashire. We collect from your site, quote for the whole batch and return every device.',
            'og_image' => 'images/og/og-business.jpg',
        ],
        'blog' => [
            'title' => 'iPhone Guides and Advice',
            'eyebrow' => 'Blog',
            'intro' => 'Practical advice from our repair team: protecting your iPhone, getting more from your battery, and what to do when something goes wrong.',
            'meta_title' => 'iPhone Guides, Tips and Repair Advice',
            'meta_description' => 'Practical iPhone advice from a Blackburn repair team: battery health, screen protection, water damage, backups and more.',
            'og_image' => 'images/og/og-blog.jpg',
        ],
        'videos' => [
            'title' => 'Repair Videos',
            'eyebrow' => 'Watch how we work',
            'intro' => 'Short videos showing how our collection service works, what happens inside your iPhone during a repair, and how to look after it afterwards.',
            'meta_title' => 'iPhone Repair Videos',
            'meta_description' => 'Short videos from ZR Technologies: how our collection and delivery repair works, screen and battery replacements, and iPhone care tips.',
            'og_image' => 'images/og/og-videos.jpg',
        ],
        'privacy-policy' => [
            'title' => 'Privacy Policy',
            'eyebrow' => 'Legal',
            'meta_description' => 'How ZR TECHNOLOGIES LTD collects, uses and protects your personal data, and your rights under UK GDPR.',
        ],
        'cookie-policy' => [
            'title' => 'Cookie Policy',
            'eyebrow' => 'Legal',
            'meta_description' => 'The cookies and similar technologies used on the ZR Technologies website, what they do and how to control them.',
        ],
        'terms-and-conditions' => [
            'title' => 'Terms and Conditions',
            'eyebrow' => 'Legal',
            'meta_description' => 'The terms that apply when you use the ZR Technologies website and services.',
        ],
        'repair-terms' => [
            'title' => 'Repair Terms',
            'eyebrow' => 'Legal',
            'meta_description' => 'The terms for iPhone repairs by ZR Technologies: diagnosis, quotes, collection, data, Activation Lock, warranty and your cancellation rights.',
        ],
        'accessibility' => [
            'title' => 'Accessibility Statement',
            'eyebrow' => 'Legal',
            'meta_description' => 'How we make the ZR Technologies website accessible, known limitations and how to contact us if something does not work for you.',
        ],
    ];

    /**
     * @return array{title: string, eyebrow?: string, intro?: string, meta_title?: string, meta_description?: string, og_image?: string}
     */
    public static function for(SystemPage $key): array
    {
        return self::PAGES[$key->value];
    }

    /**
     * An unsaved, published page with the default wording.
     */
    public static function make(SystemPage $key): Page
    {
        $defaults = self::for($key);

        $page = new Page([
            'title' => $defaults['title'],
            'slug' => $key->slug(),
            'eyebrow' => $defaults['eyebrow'] ?? null,
            'intro' => $defaults['intro'] ?? null,
            'status' => PublishStatus::Published,
            'needs_legal_review' => $key->isLegal(),
        ]);
        $page->key = $key;

        $page->setRelation('seoMeta', self::seoMeta($key));

        return $page;
    }

    public static function seoMeta(SystemPage $key): SeoMeta
    {
        $defaults = self::for($key);

        return new SeoMeta([
            'title' => $defaults['meta_title'] ?? null,
            'description' => $defaults['meta_description'] ?? null,
        ]);
    }

    /**
     * The supplied share image for the page (inside /public), used unless one is uploaded.
     */
    public static function ogImage(SystemPage $key): ?string
    {
        return self::for($key)['og_image'] ?? null;
    }
}
