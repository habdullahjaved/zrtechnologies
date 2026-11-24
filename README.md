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
| 2 | Catalogue, pricing, coverage, price and postcode checkers | Next |
| 3–5 | Booking & CRM, content & SEO, tracking & go-live | Planned |

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
| One demo account per role (password `password`) | `DemoStaffSeeder` | local/testing only |

Demo accounts: `manager@`, `technician@`, `content@`, `sales@zrtechnologies.test`.

## Staff roles

| Role | Access |
|---|---|
| Super Admin | Everything, including roles |
| Manager | Staff (except super admins), all settings, audit log |
| Marketing / Content | Website & SEO and social settings (blog, pages and SEO tools arrive in Phase 4) |
| Technician | Repair jobs (Phase 3) |
| Sales / Support | Leads and customers (Phase 3) |

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

See `routes/console.php`. Full Hostinger deployment steps (document root, SSH, `composer2`, cron, SMTP, DNS) are added in Phase 5.

## Brand assets

Source files supplied by the owner live in `zr-brand-assets/`. Web-ready copies are in `public/` (favicons, manifest), `public/brand/` (logos, with WebP variants), `public/images/{hero,og,icons}/`, and self-hosted fonts in `public/fonts/` (Montserrat and Inter, SIL Open Font License).

---

ZR Technologies is an independent repair provider and is not affiliated with or endorsed by Apple Inc.
