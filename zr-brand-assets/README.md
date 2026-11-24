# ZR Technologies — brand asset pack

Brand colours: navy `#0A1F4E` · deep navy `#00163F` · royal `#0058B0` · blue `#1E6FD9` · sky `#3FA9F5` · ice `#EEF5FF`
Fonts used: Montserrat ExtraBold (headings), Inter (body).

## Folder → where it goes in Laravel

| Folder | Copy to | Notes |
|---|---|---|
| `favicon/*` | `public/` (root) | favicon.ico, PNG icons, apple-touch-icon, site.webmanifest, browserconfig.xml |
| `logo/*` | `public/brand/` | header uses `zr-logo-horizontal-colour.png`; dark footer uses `zr-logo-horizontal-for-dark-bg.png`; Filament uses `zr-logo-full-colour-600w.png` (light) + `zr-logo-full-for-dark-bg-600w.png` (dark mode) |
| `og/*` | `public/images/og/` | 1200×630 share images, one per page (see table below) |
| `hero/*` | `public/images/hero/` | text-free backgrounds; headline/CTA are real HTML on top (better for SEO + LCP) |
| `icons/*` | `public/images/icons/` | 12 repair icons (navy for light sections, white for dark sections) for the "What we repair" grid |
| `social/*` | not for the site | upload to Facebook, LinkedIn, X, YouTube, Instagram/TikTok and Google Business Profile |

## `<head>` snippet (put in the main Blade layout)

```blade
<link rel="icon" href="/favicon.ico" sizes="48x48">
<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
<link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
<link rel="manifest" href="/site.webmanifest">
<meta name="theme-color" content="#0A1F4E">
<meta name="msapplication-config" content="/browserconfig.xml">

@php($og = $seo->og_image ?? asset('images/og/og-default.jpg'))
<meta property="og:type" content="{{ $seo->og_type ?? 'website' }}">
<meta property="og:site_name" content="ZR Technologies">
<meta property="og:locale" content="en_GB">
<meta property="og:url" content="{{ url()->current() }}">
<meta property="og:title" content="{{ $seo->title }}">
<meta property="og:description" content="{{ $seo->description }}">
<meta property="og:image" content="{{ $og }}">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="{{ $seo->og_image_alt ?? $seo->title }}">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $seo->title }}">
<meta name="twitter:description" content="{{ $seo->description }}">
<meta name="twitter:image" content="{{ $og }}">
```

## OG image → page map

| File | Page |
|---|---|
| og-default.jpg | **fallback for every page** — shows all 12 repairs we do |
| og-home.jpg | `/` (same repair-grid design) |
| og-iphone-repair.jpg | `/iphone-repair` (repair-grid design) |
| og-screen-replacement.jpg | `/iphone-repair/screen-replacement` |
| og-battery-replacement.jpg | `/iphone-repair/battery-replacement` |
| og-charging-port-repair.jpg | `/iphone-repair/charging-port-repair` |
| og-back-glass-repair.jpg | `/iphone-repair/back-glass-repair` |
| og-camera-repair.jpg | `/iphone-repair/camera-repair` |
| og-water-damage-repair.jpg | `/iphone-repair/water-damage-repair` |
| og-diagnostics.jpg | `/iphone-repair/diagnostics` |
| og-book-repair.jpg | `/book-repair` |
| og-repair-prices.jpg | `/repair-prices` (repair-grid design) |
| og-how-it-works.jpg | `/how-it-works` |
| og-areas-we-cover.jpg | `/areas-we-cover` (+ location pages until they get their own) |
| og-track-repair.jpg | `/track-repair` |
| og-about.jpg | `/about` |
| og-contact.jpg | `/contact` |
| og-faqs.jpg | `/faqs` |
| og-warranty.jpg | `/warranty` |
| og-blog.jpg | `/blog` index + category pages |
| og-videos.jpg | `/videos` |
| og-business.jpg | `/business` |
| og-template-blank.jpg | base for **auto-generated** OG images (blog posts, model pages, location pages) |

### Auto-generated OG for blog posts / model / location pages
Ask Claude Code to add: "Generate a 1200×630 OG image on save for posts, device-model pages and location pages, using `public/images/og/og-template-blank.jpg` as the background. Draw the title in Montserrat ExtraBold white (max 3 lines, left-aligned at x=70, y=200, max width 670px) with Intervention Image v3, save to `storage/app/public/og/{type}-{slug}.jpg`, and use it as that page's og:image. Regenerate when the title changes."

## Social upload guide

| File | Upload to | Notes |
|---|---|---|
| profile-picture-800x800-white.jpg | Facebook, Instagram, TikTok, X, YouTube, LinkedIn profile/logo | circle-safe |
| profile-picture-800x800-gradient.jpg | alternative profile picture (stands out more in feeds) | |
| google-business-logo-720x720.jpg | Google Business Profile → Logo | |
| google-business-cover-1024x576.jpg | Google Business Profile → Cover photo | GBP crops differently on mobile/desktop; text kept central |
| facebook-cover-1640x624.jpg | Facebook page cover | content inside mobile safe area |
| linkedin-company-banner-1128x191.jpg | LinkedIn company page | |
| x-twitter-header-1500x500.jpg | X / Twitter header | |
| youtube-channel-banner-2560x1440.jpg | YouTube channel banner | content kept in the 1546×423 safe area so it works on TV, desktop and mobile |
| instagram-post-what-we-repair-1080x1080.jpg | Instagram / Facebook feed post | pin as the first post |
| instagram-story-what-we-repair-1080x1920.jpg | Instagram / Facebook / WhatsApp Status story | save as a Highlight "Services" |
| facebook-post-what-we-repair-1200x1500.jpg | Facebook feed post (portrait) / GBP post | |
| email-header-1200x300.jpg | email newsletter / transactional email header | |
| logo/zr-logo-email-signature-300w.png | email signature | |

## Notes
- The logo was cut out from the supplied PNG (its transparency checkerboard was baked into the pixels). It's clean at web sizes. For print (business cards, van wrap, shop sign), get the **original vector (SVG/AI/EPS)** from whoever designed it.
- Small favicons (16/32/48) use the "ZR" letters only, because the full swoosh mark turns to mush at 16px.
- Hero images: `hero-home-desktop-2400x1200` (phones on the right, headline on the left) and `hero-home-mobile-1080x1350` (phones at the bottom, headline on top). Serve them with `<picture>` + `fetchpriority="high"`. Use `page-header-bg-1920x480` for inner page headers and `cta-band-bg-1920x400` for CTA strips.
- The phone illustrations are generic. There are no Apple logos anywhere, in line with the "not affiliated with Apple" disclaimer.

## The 12 repairs shown on the graphics
Screen & LCD · Battery · Charging Port · Camera & Lens · Back Glass · Water Damage · Speaker & Mic (incl. earpiece) · Face ID · Power & Volume buttons · Software Issues (boot loops, updates, restore) · Data Recovery · Free Diagnosis

**Confirm with the owner before launch.** Face ID, back glass and data recovery are specialist jobs. "Free Diagnosis" is also a promise. If any of these isn't offered, change the `REPAIRS` list in the generator script and re-run it.
