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
| 4 | Content & SEO | Next |
| 5 | Tracking & go-live | Planned |

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
| Sample leads, customers and repair jobs (names start with "Demo", emails @example.com) | `DemoCrmSeeder` | local/testing only |

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

## Staff roles

| Role | Access |
|---|---|
| Super Admin | Everything, including roles |
| Manager | Staff (except super admins), all settings, audit log, catalogue, prices, coverage, leads, customers and every repair job |
| Marketing / Content | Website & SEO and social settings; message templates; edits model and repair descriptions; views prices (blog, pages and SEO tools arrive in Phase 4) |
| Technician | Only the repair jobs assigned to them, without prices: updates status, diagnosis, IMEI and notes. Views the price list |
| Sales / Support | Leads, customers and all repair jobs (including prices); views prices, service areas and collection times. Cannot delete records |

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

Emails therefore go out within about a minute. After each deploy, run `php artisan migrate --force` and then `php artisan settings:clear-cache` (settings are cached, so new settings fields need a fresh cache).

See `routes/console.php`. Full Hostinger deployment steps (document root, SSH, `composer2`, cron, SMTP, DNS) are added in Phase 5.

## Brand assets

Source files supplied by the owner live in `zr-brand-assets/`. Web-ready copies are in `public/` (favicons, manifest), `public/brand/` (logos, with WebP variants), `public/images/{hero,og,icons}/`, and self-hosted fonts in `public/fonts/` (Montserrat and Inter, SIL Open Font License).

---

ZR Technologies is an independent repair provider and is not affiliated with or endorsed by Apple Inc.
