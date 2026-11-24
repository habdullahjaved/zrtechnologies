# ZR Technologies — website, CRM and admin

Website and back office for **ZR TECHNOLOGIES LTD** (company no. 17589309): iPhone repair with collection and delivery in and around Blackburn, Lancashire.

- **Public site:** Laravel 13, Blade, Livewire 4, Alpine, Tailwind CSS 4 (server-rendered for SEO)
- **Admin / CRM:** Filament 5 at `/admin`, with roles, permissions, two-factor sign-in and an audit log
- **Hosting target:** Hostinger shared hosting (PHP 8.5, MySQL/MariaDB, no Redis or long-running workers)

The full brief is in [`ZR_TECHNOLOGIES_BUILD_PROMPT.md`](ZR_TECHNOLOGIES_BUILD_PROMPT.md); the agreed plan and phase order are in [`docs/PHASE_0_PLAN.md`](docs/PHASE_0_PLAN.md).

## Status

| Phase | Scope | Status |
|---|---|---|
| 0 | Plan | Done |
| 1 | Foundation: admin panel, roles, MFA, settings, design system, layout | Done |
| 2 | Catalogue, pricing, coverage, price and postcode checkers | Done |
| 3 | Booking wizard, leads, customers, repair jobs, emails, repair tracking | Done |
| 4 | Content & SEO: pages, repair, model and location pages, blog, videos, FAQs, reviews, schema, sitemaps, redirects | Done |
| 5 | Tracking & go-live: cookie banner, Consent Mode v2, GA4 / Google Ads / Meta (browser and server), offline conversions, review requests, security headers, Hostinger deployment | Done |
| 6 | CRM depth: Kanban, tasks, quotes and invoices, checklists, PDFs, inventory | Planned (after launch) |
| 7 | Dashboard and reports | Planned (after launch) |

## Requirements

- PHP **8.5** with `curl`, `exif`, `fileinfo`, `gd` (WebP), `intl`, `mbstring`, `openssl`, `pdo_mysql`, `sodium`, `zip`
- MySQL 8.4 (or MariaDB 10.11+)
- Composer 2
- Node.js 20.19+ (22 LTS recommended) for building front-end assets

## Local setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Edit `.env`:

| Variable | Notes |
|---|---|
| `DB_*` | Local MySQL connection (port 3306 by default) |
| `ADMIN_EMAIL`, `ADMIN_NAME` | The owner's super admin account |
| `ADMIN_PASSWORD` | Optional. If empty, the seeder generates a strong password and prints it once |
| `ADMIN_REQUIRE_MFA` | `true` in production. May be `false` locally for quicker sign-in |

Then:

```bash
php artisan migrate --seed
npm run build
php artisan serve
```

- Website: <http://127.0.0.1:8000>
- Admin: <http://127.0.0.1:8000/admin>
- Design system reference (local only): <http://127.0.0.1:8000/styleguide>

### Running under XAMPP (Windows)

XAMPP's Apache runs PHP 8.3, but this project needs PHP 8.5, installed separately in `C:\php\8.5`. Without changing XAMPP for other sites:

- `C:\php\8.5\fastcgi\start-php85-fastcgi.vbs` starts four PHP 8.5 FastCGI workers (ports 9085–9088); `stop-php85-fastcgi.cmd` stops them. Put a shortcut to the `.vbs` in the Windows Startup folder so they start at log-in.
- `C:\xampp\apache\conf\extra\httpd-php85-zrtechnologies.conf` (included at the end of `httpd.conf`) sends `.php` requests in this folder to those workers and adds <http://localhost:8085>, which serves `public/` as the site root. Every other site keeps PHP 8.3.
- Restart Apache from the XAMPP Control Panel after changing either file. To undo, remove the `Include` line from `httpd.conf` (a backup is in `httpd.conf.before-php85.bak`).

### What the database is seeded with

| Data | Source | Environments |
|---|---|---|
| Business details (NAP), opening hours, social links, SEO defaults | settings migrations in `database/settings` | all |
| Staff roles and permissions | `RolesAndPermissionsSeeder` | all |
| Owner's super admin account | `AdminUserSeeder` (from `ADMIN_*`) | all |
| iPhone models (iPhone 8 to iPhone 18 Pro Max), 12 repair types, part qualities, **draft** price list | `CatalogueSeeder` | all (only creates what is missing) |
| Draft service areas, weekly collection timetable, bank holidays | `CoverageSeeder` | all (only creates what is missing) |
| Wording of every customer email, team alert and WhatsApp reply | `MessageTemplateSeeder` | all (only creates what is missing) |
| One demo account per role (password `password`) | `DemoStaffSeeder` | local/testing only |
| Website content: every page with its wording, legal drafts, ten repair pages, 28 FAQs, blog categories and six draft posts, video placeholders, menus, local content for the location pages | `ContentSeeder` | all (only creates what is missing) |
| Sample leads, customers and repair jobs (names start with "Demo", emails @example.com) | `DemoCrmSeeder` | local/testing only |
| Sample reviews, labelled and never shown in production | `DemoContentSeeder` | local/testing only |

Demo accounts: `manager@`, `technician@`, `content@`, `sales@zrtechnologies.test`.

## Prices and coverage

Every seeded price and service area is marked **DRAFT – confirm before launch**. In **Admin → Catalogue → Price list** you can edit prices inline, change a selection by a percentage, confirm drafts in bulk, and download or upload the whole list as CSV (Excel / Google Sheets). Postcode checks use [postcodes.io](https://postcodes.io) (free, no key), cached in the `postcode_lookups` table; if it is unreachable, the check falls back to the postcode district.

## Bookings and the CRM

- **Online booking** (`/book-repair`): postcode → device → repairs → collection slot → details → confirm. Prices and slots are re-checked on the server when the booking is saved, and a slot is locked while it is taken, so windows are never overbooked. Links such as `/book-repair?model=iphone-15&repair=screen-replacement&postcode=BB2 4NY` open the form prefilled (the price checker and postcode checker use them).
- **Unfinished bookings** are kept as leads as soon as the visitor gives a mobile or email.
- **Other ways in:** contact, callback and quote forms (`/contact`), business enquiries (`/business`), the quote form on `/repair-prices`, and out-of-area requests from the booking form. Each becomes a lead with a reference such as `ENQ-2026-000045`.
- **Where they came from:** click IDs (`gclid`, `gbraid`, `wbraid`, `fbclid`, `msclkid`, `ttclid`), UTM tags, landing page and referrer are captured on arrival and saved on the lead with a channel (Google Ads, Google Business Profile, Meta ads, organic…). Tag the Google Business Profile website link with `?utm_source=google&utm_medium=organic&utm_campaign=gbp`. Phase 5 adds the cookie banner, a 90-day first-party cookie (with consent) and sending conversions to Google and Meta.
- **Repair jobs** have references such as `ZR-2026-000123`, a full status flow with history, technician assignment and an encrypted IMEI/serial number (searchable by IMEI). Changing a status can email the customer.
- **Customers track repairs** at `/track-repair` with their reference and postcode, or straight from the link in their emails.
- **Emails** (booking confirmation, enquiry received, repair updates, team alerts) are queued and use the wording in **Admin → Settings → Message templates**. Team alerts can also go to Slack or Telegram (**Settings → Team alerts**).
- **Spam protection:** a hidden honeypot field, a minimum fill time and per-visitor rate limits on every public form, with no CAPTCHA. Cloudflare Turnstile can be added in Phase 5 if spam gets through.

## Content and SEO

- **Pages:** every page has its heading, introduction, content blocks, FAQs and SEO in **Admin → Content → Pages**. Site pages keep a fixed address and cannot be deleted; custom pages are served at `/{slug}`. Legal pages are drafts flagged **Needs legal review** until checked.
- **Repair pages** (`/iphone-repair/{slug}`) cover one or more repair types and show live prices for every model. **Model pages** (`/iphone-repair/iphone-15-pro`) are built from the price list. Optional **model × repair pages** are switched on per model.
- **Location pages** (`/iphone-repair-{town}`) and model × repair pages stay `noindex` (canonical to the parent page) and out of the sitemap until their own content passes the unique-content check: enough words, and no more than 50% similar to a sibling (thresholds in **Settings → Website & SEO**).
- **SEO fields** on every page, post, repair page, model, area and video: title (with a pixel-width guide), description, focus keyword, Google preview, checklist, noindex/nofollow, canonical, share image and extra JSON-LD. Empty fields fall back to the **title patterns** in Website & SEO settings.
- **Structured data:** LocalBusiness (service-area: the street address is hidden unless switched on in Business settings), WebSite, WebPage, BreadcrumbList, Service with live prices, FAQPage (only where FAQs are visible), BlogPosting, Person and VideoObject.
- **Sitemaps:** `/sitemap.xml` indexes `/sitemaps/{pages,services,models,locations,blog,videos}.xml`, with image and video entries, and only lists indexable pages. `robots.txt` adds the sitemap and any extra rules from settings.
- **Redirects:** changing a slug redirects the old address automatically (no chains). The **404 log** (Admin → SEO) shows missing addresses with a one-click "Create redirect".
- **Blog:** draft → ready for review → scheduled/published, table of contents, reading time, last-updated date, share links, author pages, related posts, internal-link suggestions in the editor and an RSS feed at `/blog/feed`. Tag pages are `noindex` unless switched on.
- **Videos:** placeholders show a branded "coming soon" card; YouTube videos load only when played (privacy-enhanced mode).
- **Reviews:** genuine reviews only (copy them from Google). Sample reviews exist only locally and never show in production. Set the Google review link in **Settings → Social** to power `/reviews/leave`.
- **Page cache:** public pages are cached for anonymous visitors and cleared automatically when content, prices or settings change, and after each `npm run build`. Disable with `PAGE_CACHE_ENABLED=false`.

## Tracking and going live

- **Cookie banner** with Accept all / Reject all / Manage preferences, Google Consent Mode v2 (denied by default) and a server-side consent log (**Marketing → Consent log**).
- **Tags** (Google Tag Manager, or GA4 + Google Ads + Meta Pixel + Clarity + TikTok directly) load only as consent allows. IDs go in **Settings → Tracking**.
- **Server-side conversions:** Meta Conversions API for every lead (deduplicated with the Pixel by event ID), GA4 Measurement Protocol and Meta `Purchase` when a repair is completed, and a Google Ads **offline conversions CSV** for scheduled upload. All logged in **Marketing → Tracking events**, retried automatically.
- **Marketing → Tracking health:** what is set up, what was sent, test buttons, the offline conversions link and ready-made Google Business Profile links.
- **Review requests:** one email a few hours after a repair is completed (needs the Google review link in Settings → Social), plus an "Ask for a review (WhatsApp)" button on each job.
- **Security:** Content-Security-Policy with per-request nonces on public pages, HSTS in production, HTTPS and non-www redirects for the live domain.
- Full guides: [`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md) (Hostinger) and [`docs/TRACKING.md`](docs/TRACKING.md) (events, GTM container, Google Ads and Meta setup). Run `php artisan launch:check` before going live.

## Staff roles

| Role | Access |
|---|---|
| Super Admin | Everything, including roles |
| Manager | Staff (except super admins), all settings, audit log, catalogue, prices, coverage, leads, customers and every repair job |
| Marketing / Content | Pages, repair pages, blog, authors, videos, FAQs, reviews, menus, redirects and the 404 log; Website & SEO and social settings; message templates; model and repair descriptions; views prices |
| Technician | Only the repair jobs assigned to them, without prices: updates status, diagnosis, IMEI and notes. Views the price list |
| Sales / Support | Leads, customers and all repair jobs (including prices); adds customer reviews; views prices, service areas and collection times. Cannot delete records |

Permissions are defined in `database/seeders/RolesAndPermissionsSeeder.php` and can be fine-tuned in **Admin → Users & security → Roles & permissions**.

## Quality checks

```bash
php artisan test            # Pest, against the MySQL test database (DB_DATABASE=zrtechnologies_testing)
vendor/bin/phpstan analyse  # Larastan, level 6
vendor/bin/pint             # code style
```

## Scheduled tasks and queues

Shared hosting has no long-running queue worker, so a single cron entry runs the scheduler every minute, and the scheduler processes queued jobs:

```
* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
```

Emails therefore go out within about a minute, and scheduled pages and posts are published within five minutes of their date. After each deploy, run `php artisan migrate --force` and then `php artisan settings:clear-cache` (settings are cached, so new settings fields need a fresh cache).

See `routes/console.php`. Hostinger deployment (document root, SSH, `composer2`, cron, SMTP, SPF/DKIM/DMARC) is in [`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md).

## Brand assets

Source files supplied by the owner live in `zr-brand-assets/`. Web-ready copies are in `public/` (favicons, manifest), `public/brand/` (logos, with WebP variants), `public/images/{hero,og,icons}/`, and self-hosted fonts in `public/fonts/` (Montserrat and Inter, SIL Open Font License).

---

ZR Technologies is an independent repair provider and is not affiliated with or endorsed by Apple Inc.
