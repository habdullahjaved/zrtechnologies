# BUILD BRIEF — ZR Technologies Ltd website, CRM and admin (Laravel 13 + Filament v5)

> **How to use this file:** put it in the project root (e.g. `docs/BUILD_BRIEF.md`) and tell Claude Code:
> "Read docs/BUILD_BRIEF.md in full. Start with Phase 0: give me the plan, the database schema and the package list for approval. Then build phase by phase, committing after each phase and stopping at the end of each one so I can review."

---

## 0. Your role and how to work

You are a senior Laravel architect, technical SEO specialist and conversion-tracking engineer. Build a production-grade, fully working website + CRM for **ZR Technologies Ltd**, a UK company. The first service is **iPhone repair with collection and delivery** in and around Blackburn, Lancashire. The same codebase must later grow into phone accessories, custom-printed products (T-shirts, mugs), web development and other IT services, without rewrites.

Working rules:
1. **Plan first.** Before writing code, output: architecture overview, full ERD/migration list, package list with versions, route map, and the phase plan below. Wait for approval.
2. **Build in phases** (section 18). Each phase must end in a working, tested state with a git commit.
3. **Nothing fake or half-done.** No placeholder controllers, no `// TODO` in shipped code, no dead buttons. Every form saves, every admin page works, every tracking event fires.
4. **Everything editable from the admin.** Business details, prices, service areas, pages, SEO fields, tracking IDs, chatbot settings: none of them are hard-coded.
5. Use **Laravel Boost** (if installed) / official docs for Laravel 13, Filament v5, Livewire v4 and the Laravel AI SDK. Check the current docs rather than guessing APIs.
6. Write **Pest** tests for booking, lead capture, attribution, pricing, postcode checks and the chatbot tools.
7. UK English throughout (colour, optimise, postcode, mobile), GBP (£), DD/MM/YYYY dates, Europe/London timezone, UK phone formatting.

---

## 1. Business facts (NAP — must be identical everywhere: site, schema, footer, Google Business Profile)

| Field | Value |
|---|---|
| Legal name | ZR TECHNOLOGIES LTD |
| Trading name | ZR Technologies |
| Company number | 17589309 (registered in England & Wales) |
| Registered office | 98 Moorgate Street, Blackburn, England, BB2 4NY |
| Owner / director | Muhammad Umar Shabbir |
| Phone | +44 7360 127538 (display as `07360 127538`, link as `tel:+447360127538`) |
| WhatsApp | +44 7360 127538 (`https://wa.me/447360127538`) |
| Email | mrumar@zrtechnologiesltd.com |
| Domain | https://zrtechnologiesltd.com (force HTTPS, non-www canonical, 301 www → non-www) |
| Tagline | Innovate • Develop • Grow |
| Business model (phase 1) | Mobile/collection-and-delivery iPhone repair. Customer books online → technician collects the phone → repairs it → returns it. Service-area business (no walk-in shop yet) |

Store all of this in a **Settings** model (spatie/laravel-settings) editable in Filament, and pull it from there in views, schema, emails and the chatbot.

---

## 2. Tech stack (use current stable versions)

- **Laravel 13** (PHP 8.3+), MySQL 8 (or MariaDB 10.11+), Redis for cache, queues and sessions
- **Filament v5** (Livewire v4, Tailwind CSS v4) for the admin/CRM
- Public front end: **Blade + Livewire v4 + Alpine.js + Tailwind v4**, server-rendered for SEO (no SPA)
- **laravel/ai** (Laravel AI SDK) for the chatbot agent; **laravel/mcp** for an MCP server exposing CRM tools
- Laravel Horizon (queues), Laravel Scheduler, Laravel Pulse or Nightwatch (monitoring)
- Suggested packages (confirm versions/compatibility with Laravel 13 + Filament v5 before installing):
  - `bezhansalleh/filament-shield` + `spatie/laravel-permission` — roles/permissions
  - `spatie/laravel-medialibrary` + Filament plugin — images/videos with WebP/AVIF conversions and responsive srcset
  - `spatie/laravel-settings` + Filament plugin — site settings
  - `spatie/laravel-sitemap` — XML sitemaps
  - `spatie/schema-org` — JSON-LD
  - `spatie/laravel-activitylog` — audit trail on every CRM record
  - `spatie/laravel-backup` — daily DB + media backups (off-site disk)
  - `spatie/laravel-honeypot` + Cloudflare Turnstile — spam protection (no annoying CAPTCHAs)
  - `spatie/laravel-responsecache` — full-page cache for public pages (bust on content save)
  - `barryvdh/laravel-dompdf` or `spatie/laravel-pdf` — job sheets, quotes, invoices
  - `propaganistas/laravel-phone` — UK phone validation/formatting
  - postcodes.io (free UK API) — postcode validation + lat/lng + district lookup
- Pest, Larastan (level 6+), Pint

---

## 3. Brand and design system

**Logo:** use only the ZR logo mark + "ZR TECHNOLOGIES" wordmark from the supplied banner (`/public/brand/` — I will provide `logo.png`/`logo.svg`; also create a favicon set, apple-touch-icon, maskable PWA icon and a 1200×630 OG default image). **Ignore everything else on the banner** (the world map, laptop and service icons are not part of the brand).

**Colour palette (derived from the logo):**

| Token | Hex | Use |
|---|---|---|
| `navy-950` | `#00163F` | darkest text, footer background |
| `navy-900` (primary) | `#0A1F4E` | headings, primary dark surfaces, the "ZR" letterforms |
| `royal-600` (brand) | `#0058B0` | primary buttons, links, active states |
| `blue-500` | `#1E6FD9` | hover, gradients, icons |
| `sky-400` (accent) | `#3FA9F5` | swoosh highlight, glows, badges, focus rings |
| `ice-50` | `#EEF5FF` | section backgrounds, cards |
| `white` | `#FFFFFF` | base |
| `slate-600` | `#475569` | body text |
| Success / warning / danger | `#16A34A` / `#F59E0B` / `#DC2626` | status only |

- Signature gradient: `linear-gradient(135deg, #0A1F4E 0%, #0058B0 55%, #3FA9F5 100%)` — use for the hero, CTA bands and the Filament login page. Echo the logo's **orbital swoosh** as a subtle SVG arc motif in the hero and section dividers.
- Apply the same palette to Filament (`->colors(['primary' => ...])`, custom theme, brand logo, dark mode supported).
- All text/background pairs must pass **WCAG 2.2 AA** contrast.
- Typography: headings **Montserrat** (700/800, matches the wordmark), body **Inter**; self-host fonts (no Google Fonts request), `font-display: swap`, preload the heading weight.
- Feel: premium, clean, trustworthy, modern tech. Generous white space, soft shadows, rounded-2xl cards, micro-animations (respect `prefers-reduced-motion`), crisp device illustrations/photography. Mobile-first — most visitors will be on iPhones.
- Persistent conversion UI on mobile: sticky bottom bar with **Call**, **WhatsApp**, **Book Repair**. Desktop: sticky header with phone number + "Book a Repair" button.

---

## 4. Site architecture (information architecture and URLs)

Clean, lowercase, hyphenated, no trailing slash, no IDs in URLs. Every page has an admin-editable SEO title, meta description, H1, OG image, canonical and index/noindex toggle.

### Core pages
- `/` — Home
- `/iphone-repair` — **Pillar page: iPhone Repair Blackburn** (the main money page)
- `/book-repair` — multi-step booking wizard
- `/repair-prices` — interactive price checker (model → repair → price)
- `/how-it-works` — collection → diagnosis → repair → return, with timings
- `/areas-we-cover` — map + postcode checker + list of areas
- `/track-repair` — customer enters job reference + postcode to see status
- `/about`, `/contact`, `/reviews`, `/faqs`, `/warranty`
- `/blog`, `/blog/{category}`, `/blog/{slug}`
- `/videos` — shorts gallery
- `/business` — business/bulk repairs (fleets, schools, offices)
- Legal: `/privacy-policy`, `/cookie-policy`, `/terms-and-conditions`, `/repair-terms`, `/accessibility`

### Service pillar + cluster pages (iPhone, phase 1)
Each repair type is a **pillar page** linking down to model pages and up to `/iphone-repair`:
- `/iphone-repair/screen-replacement`
- `/iphone-repair/battery-replacement`
- `/iphone-repair/charging-port-repair`
- `/iphone-repair/back-glass-repair`
- `/iphone-repair/camera-repair` (rear camera, front camera, camera lens)
- `/iphone-repair/water-damage-repair`
- `/iphone-repair/speaker-microphone-repair`
- `/iphone-repair/face-id-repair` (only if offered — toggle)
- `/iphone-repair/data-recovery` (only if offered — toggle)
- `/iphone-repair/diagnostics`

### Model pages (programmatic, but never thin)
- `/iphone-repair/{model}` e.g. `/iphone-repair/iphone-15-pro-max` — lists every repair + price for that model, model-specific notes (e.g. which screens are OLED, battery health messages), FAQs and a "Book this repair" CTA prefilled with the model.
- Optional deeper level `/iphone-repair/{model}/{repair}` **only** when the admin enables it and has written unique content (minimum unique-content checks in admin; otherwise `noindex` + canonical to the model page). Prevent doorway/thin pages.
- Seed models from iPhone 8 / SE (2nd gen) up to the latest current lineup; models are admin-managed so new releases are added in minutes.

### Location pages (local SEO)
- `/iphone-repair-{town}` e.g. `/iphone-repair-blackburn`, `/iphone-repair-darwen`, `/iphone-repair-accrington`
- Only towns actually served (driven by the Service Area module). Each page must have genuinely local unique content (admin-written): area served, postcodes covered, typical collection times, local landmarks/neighbourhoods, local reviews, embedded map of coverage. Admin warns if content is under ~400 unique words or duplicates another location page.
- Seed draft service areas (owner to confirm): Blackburn (BB1, BB2), Darwen (BB3), Accrington/Oswaldtwistle (BB5), Great Harwood/Rishton (BB6), Clitheroe (BB7), plus optional Preston (PR1–PR2), Burnley (BB10–BB12), Chorley (PR6–PR7).

### Future modules (build the structure now, hidden behind feature flags)
- `/shop` — phone accessories (cases, screen protectors, chargers, cables) — stock also sold on eBay
- `/custom-printing` — custom T-shirts, mugs, etc. (product + artwork upload + quote)
- `/web-development`, `/it-services`, `/seo-services`, `/social-media-management`, `/graphic-design`
- A **Service Category → Service** hierarchy so new verticals get their own pillar pages, booking/quote forms and CRM pipelines without code changes. Feature flags in Settings switch each vertical on/off (hidden from nav, sitemap and robots when off).

### Navigation
- Header: iPhone Repair (mega-menu: repair types + popular models + "All prices") · Prices · How It Works · Areas · Blog · Contact · [Book a Repair] button · phone number
- Footer: NAP block (matches GBP exactly), service links, area links, legal links, company number + registered office, social links, "Independent repair specialist — not affiliated with Apple Inc." disclaimer
- Breadcrumbs on every page except home (with BreadcrumbList schema)

---

## 5. The booking flow (core conversion — make it excellent)

Multi-step Livewire wizard, saves progress (so abandoned bookings are captured as partial leads once a phone or email is entered):

1. **Postcode check** — validate via postcodes.io, check against service areas (by postcode district and/or radius from base). In area → continue. Out of area → offer "post-in repair" (if enabled) or capture as an out-of-area lead.
2. **Device** — pick model (visual grid with search), colour (optional), storage (optional).
3. **Problem** — pick one or more repairs (with live price, parts quality option e.g. "Premium" vs "Genuine/OEM" if offered, warranty length per option) or "Not sure — free diagnosis".
4. **Collection slot** — date + time window from an admin-managed availability calendar (working days, slots per window, blackout dates, lead time, same-day cutoff). Return option: deliver to same address / different address.
5. **Details** — name, mobile, email, address (postcode lookup prefill), passcode policy notice (we never ask for the passcode in the form; explain backup and Find My (Activation Lock) requirements), consent checkboxes (terms required; marketing opt-in separate and unticked).
6. **Review + confirm** — summary, total estimate, payment note (pay on return / card link), booking reference (e.g. `ZR-2026-000123`).

On submit:
- Create **Customer**, **Lead** (with full attribution — section 8) and **Repair Job**.
- Fire conversion events (GA4, Google Ads, Meta Pixel + Conversions API) with a shared `event_id` for de-duplication.
- Send confirmation email (branded, queued) + admin notification (email + optional WhatsApp/Telegram/Slack webhook).
- Thank-you page at `/book-repair/thank-you?ref=...` (noindex) with next steps, add-to-calendar link, WhatsApp button.

Also provide lighter conversion paths, each a tracked lead type: **Quick quote form**, **Callback request**, **Contact form**, **WhatsApp click**, **Call click**, **Chatbot lead**, **Business enquiry**.

---

## 6. Data model (minimum — extend where sensible)

- `customers` (name, phone, email, addresses, marketing consent + timestamp/source, notes, lifetime value, tags)
- `addresses`
- `device_brands` (Apple first; others later), `device_models` (name, slug, release year, image, display type, sort, active)
- `repair_types` (name, slug, icon, description, typical duration, active)
- `repair_prices` (model × repair type × part quality → price, sale price, warranty days, in-stock flag, active) — bulk-editable grid in admin + CSV import/export
- `service_areas` (name, slug, postcode districts, centre lat/lng, radius, collection fee, active, linked location page)
- `availability_slots`, `blackout_dates`
- `leads` (type, status, source/medium/campaign, all attribution fields, assigned user, score, value, lost reason, linked customer/job)
- `repair_jobs` (reference, customer, device, IMEI/serial, condition on collection (checklist + photos), reported fault, diagnosis, repairs, parts used, quoted price, final price, technician, status, timestamps per status, warranty expiry, customer signature on collection/return)
- `job_status_history` (status, note, user, notified_customer flag)
- `parts` / `inventory_items` (SKU, name, compatible models, supplier, cost, stock qty, reorder level) + stock movements — shared later with the shop and eBay stock
- `suppliers`
- `quotes`, `invoices`, `payments` (UK VAT-ready fields; VAT can be switched on when registered)
- `posts`, `post_categories`, `tags`, `authors` (blog)
- `pages` (CMS pages with block builder)
- `faqs` (attachable to any page/service/model)
- `videos` (shorts)
- `reviews` (only genuine — source, reviewer name, rating, text, date, verified flag, linked job)
- `redirects` (301/302 manager), `seo_meta` (polymorphic)
- `chat_conversations`, `chat_messages`
- `tracking_events` (server-side log of every conversion event sent, platform, payload hash, response, status — for debugging)
- `ebay_listings` (future: item ID, linked inventory item, price, qty)

---

## 7. SEO requirements (technical + on-page)

- Server-rendered HTML, one H1 per page, logical H2/H3 hierarchy, semantic HTML5 landmarks.
- Admin-editable per page: title (with pixel-width/length helper), meta description, slug, canonical, robots, OG/Twitter fields and image, focus keyword, schema overrides. Show a live **SERP preview** and an SEO checklist in Filament.
- Auto-generated, admin-overridable title patterns, e.g. `{Repair} for {Model} in Blackburn | From £{price} | ZR Technologies`.
- **XML sitemaps** split by type (pages, services, models, locations, blog, videos) + sitemap index; auto-regenerated on content change; only indexable URLs included. Image and video sitemap entries.
- `robots.txt` managed in admin (block `/admin`, `/book-repair/thank-you`, search/filter params, staging environments entirely).
- **Structured data (JSON-LD)** built from Settings/DB, validated against Google Rich Results:
  - `LocalBusiness` → subtype `MobilePhoneStore` / `ElectronicsStore` or generic `LocalBusiness` with `areaServed` (service-area business: list towns/postcodes; don't show a street address publicly if the owner chooses to hide it in GBP — make this a setting), `telephone`, `email`, `url`, `logo`, `image`, `openingHoursSpecification`, `sameAs` (GBP, Facebook, Instagram, TikTok, YouTube, eBay store), `priceRange`
  - `Organization` (legal name, company number via `identifier`, founder)
  - `Service` + `Offer`/`AggregateOffer` on service and model pages (real prices from DB)
  - `FAQPage` where FAQs render visibly on the page
  - `BreadcrumbList`, `Article`/`BlogPosting` (author, dates, image), `VideoObject` for shorts, `HowTo` where genuinely step-based, `Review`/`AggregateRating` **only** for first-party reviews displayed on the page and compliant with Google's guidelines
- Internal linking engine: related repairs, related models, related posts; blog posts auto-suggest links to pillar pages; pillar pages list their latest cluster posts.
- 301 redirect manager + automatic redirect when a slug changes; 404 logger in admin with "create redirect" action.
- Pagination with proper links, no index bloat from filters/tags (tag pages noindex unless enabled).
- Hreflang not needed (en-GB only) — set `<html lang="en-GB">` and `og:locale=en_GB`.
- **Core Web Vitals targets:** LCP < 2.0s, INP < 200ms, CLS < 0.05 on mobile 4G. Lighthouse ≥ 95 for Performance, Accessibility, Best Practices and SEO on home, pillar, model, location and blog pages. Use responsive WebP/AVIF images, lazy loading below the fold, `fetchpriority="high"` on the LCP image, critical CSS, deferred JS, full-page cache, CDN-ready asset URLs. Tracking scripts must not wreck CWV (load via GTM after consent, use `defer`).

---

## 8. Conversion tracking and attribution (must be complete and reliable)

### 8.1 Consent first (UK GDPR + PECR)
- Build a first-party **cookie consent banner** (categories: necessary, analytics, marketing; "Accept all", "Reject all" with equal prominence, "Manage preferences"; consent log stored server-side with timestamp, version, and IP hash).
- Implement **Google Consent Mode v2** (`ad_storage`, `analytics_storage`, `ad_user_data`, `ad_personalization`) default `denied`, updated on choice. Meta Pixel only loads after marketing consent (use `fbq('consent', 'revoke'/'grant')`).
- Admin can switch to a third-party CMP later (Cookiebot/CookieYes) via a setting.

### 8.2 Tag setup
- **Google Tag Manager** container ID, **GA4** measurement ID, **Google Ads** conversion ID + labels, **Meta Pixel** ID, **Meta CAPI** access token, **Microsoft Clarity** ID, optional **TikTok Pixel** — all entered in Filament Settings (never hard-coded), with a "test mode" toggle.
- Push a clean, documented `dataLayer` for every event. Provide a GTM container JSON export in `/docs/gtm-container.json` and a README explaining each tag/trigger/variable.

### 8.3 Click ID and UTM capture (first-party, server-side)
- On landing, capture and persist (first-party cookie + session, 90-day window, both **first-touch and last-touch**): `gclid`, `gbraid`, `wbraid`, `fbclid` (build `_fbc`), `_fbp`, `msclkid`, `ttclid`, `utm_source`, `utm_medium`, `utm_campaign`, `utm_term`, `utm_content`, landing page, referrer, device type, GA4 `client_id` and `session_id`.
- Derive a **channel** for every lead: Google Ads, Google Organic, Google Business Profile (use UTM `utm_source=google&utm_medium=organic&utm_campaign=gbp` on the GBP website link and booking link), Meta Ads, Meta Organic, Direct, Referral, Email, Chatbot, WhatsApp, Phone.
- Store all of it on the **Lead** record and show it in the CRM (a clear "Attribution" tab).

### 8.4 Events (browser + server)
| Event | GA4 name | Google Ads conversion | Meta event |
|---|---|---|---|
| Booking completed | `generate_lead` / `book_repair` | Primary: "Repair Booking" (value = estimate) | `Lead` (+ `Schedule`) |
| Quote form | `generate_lead` (type=quote) | Secondary | `Lead` |
| Callback request | `generate_lead` (type=callback) | Secondary | `Lead` |
| Contact form | `generate_lead` (type=contact) | Secondary | `Contact` |
| Call click | `click_to_call` | "Phone call clicks" | `Contact` |
| WhatsApp click | `click_whatsapp` | Secondary | `Contact` |
| Chatbot lead captured | `chatbot_lead` | Secondary | `Lead` |
| Booking step views | `begin_checkout`-style funnel steps `booking_step_1..6` | — | `InitiateCheckout` on step 1 |
| Price checker used | `view_price` | — | `ViewContent` |
| Job completed & paid (offline) | `purchase` via Measurement Protocol | Offline conversion "Repair Completed" (value = final price) | `Purchase` via CAPI |

- **Meta Conversions API**: send server-side events from a queued job with the same `event_id` as the browser Pixel event (dedupe), hashed `em`/`ph`/`fn`/`ln`/`zp`/`ct`/`country=gb` (SHA-256, normalised), `fbc`, `fbp`, `client_ip_address`, `client_user_agent`, `event_source_url`, `action_source=website` (or `system_generated` for offline). Respect consent.
- **Google Ads Enhanced Conversions for leads**: pass hashed email/phone with the browser conversion (user-provided data via gtag/GTM).
- **Offline conversion import**: when a lead's job is marked **Completed/Paid** in the CRM, queue an upload of the conversion with its `gclid`/`gbraid`/`wbraid` (or enhanced-conversion identifiers) and the real value. Implement via the Google Ads API (credentials in Settings) **and** provide a scheduled CSV export endpoint/file as a fallback for manual/scheduled upload. Same for Meta (CAPI `Purchase` with the original lead's identifiers).
- **GA4 Measurement Protocol** for server-side lead + offline purchase events (using stored `client_id`).
- Log every server-side send in `tracking_events` with status + response, retry failures with backoff, and show a **Tracking Health** page in Filament (last events, failures, which IDs are configured, a "send test event" button for Meta Test Events and GA4 DebugView).
- Call tracking: track click-to-call now; leave an integration point for a call-tracking number pool (e.g. dynamic number insertion) later.

---

## 9. Local SEO and Google Business Profile support

- Location pages + `areaServed` schema as above; consistent NAP everywhere.
- Admin "GBP" settings: GBP profile URL, review link (`https://g.page/r/.../review`), Maps embed, primary category suggestion ("Mobile phone repair shop") and opening hours (single source of truth used in schema and footer).
- **Review request automation:** when a job is marked Returned/Completed, queue an email (and a WhatsApp click-to-send message template the technician can send) asking for a Google review, with the GBP review link. Only ask genuine customers; never incentivise or gate reviews.
- Generate UTM-tagged links for the GBP website button, booking button and GBP posts (displayed in admin for copy-paste).
- Embed a coverage map (Leaflet + OpenStreetMap tiles to avoid API costs, or Google Maps if a key is set) on `/areas-we-cover` and location pages.
- Citation checklist page in admin (Yell, Thomson Local, Bing Places, Apple Business Connect, Facebook, Trustpilot) with fields to store each listing URL (feeds `sameAs`).

---

## 10. Blog / guides (publishing daily or weekly)

- Filament post editor with a rich block builder (headings, text, image, gallery, callout/tip, warning box, comparison table, FAQ block, video/short embed, CTA block "Book this repair", product block for future accessories).
- Fields: title, slug, excerpt, featured image (alt text required), category, tags, author (with author bio page + `Person` schema for E-E-A-T), reading time, published/updated dates, **scheduled publishing**, draft/review/published workflow, related posts, linked pillar page, SEO fields.
- Auto table of contents, estimated reading time, "Last updated" date, social share buttons, author box, related posts, inline CTA to booking.
- RSS feed at `/blog/feed`.
- Seed categories: **iPhone Care & Protection**, **Battery Health**, **Screen & Glass**, **Water Damage**, **Buying Guides (cases, protectors, chargers)**, **Troubleshooting**, **Security & Privacy**, **News & Updates**.
- Seed 6 draft cornerstone posts (titles + outlines, marked DRAFT for the owner to complete), e.g. "How to protect your iPhone screen: tempered glass vs film", "iPhone battery health explained: when to replace it", "Dropped your iPhone in water? What to do in the first hour", "Original vs aftermarket iPhone screens: what UK buyers should know", "How to back up your iPhone before a repair", "Best ways to make your iPhone battery last longer".
- **AI writing assistant in the editor** (Laravel AI SDK): generate outline, meta description suggestions, FAQ suggestions and internal-link suggestions from the draft. The human writes/approves the final text; never auto-publish AI content.

---

## 11. Video shorts

- `videos` module: title, slug, description, file upload (MP4/WebM) **or** YouTube Shorts / TikTok / Instagram Reel URL, poster image, duration, transcript, linked service/model/post, sort order, active.
- Front end: vertical 9:16 cards with click-to-play (lazy, no autoplay with sound, `facade` pattern for YouTube embeds so they don't hurt performance), carousel on home and service pages, `/videos` gallery, individual video pages with `VideoObject` schema and video sitemap.
- Until real videos exist, render branded **placeholder cards** ("Video coming soon — {service}") controlled from admin, so the layout is final now.
- Planned shorts (seed as placeholders): "How our collection & delivery repair works", "iPhone screen replacement in 60 seconds", "Battery replacement", "Charging port clean vs repair", "Meet the technician", "Protect your new iPhone".

---

## 12. Admin panel and CRM (Filament v5) — professional and scalable

### Panels and roles
- `/admin` panel. Roles: **Super Admin**, **Manager**, **Technician** (sees assigned jobs, updates statuses, uploads photos, no financials), **Marketing/Content** (blog, pages, SEO, videos), **Sales/Support** (leads, customers, chat). Permissions via Filament Shield. 2FA for all staff (Filament's built-in MFA). Activity log on all records.

### Dashboard (beautiful, branded, useful)
- KPI cards: leads today/7d/30d, bookings, conversion rate, jobs in progress, jobs awaiting collection, jobs ready for return, revenue (week/month), average job value, average turnaround time.
- Charts: leads by channel (Google Ads vs Organic vs GBP vs Meta vs Direct vs Chatbot), leads → bookings → completed funnel, revenue by repair type, top models, top locations/postcodes, cost-per-lead (if ad spend entered manually or imported).
- Today's schedule widget: collections and returns for today in route order with Google Maps directions links.
- Recent leads table with quick actions (call, WhatsApp, convert to job).
- Tracking health and stock alerts widgets.

### Leads / CRM
- Kanban board **and** table views. Pipeline stages: New → Contacted → Quoted → Booked → Collected → Completed → Lost (with lost reason). Pipelines configurable per service vertical.
- Lead scoring, assignment, follow-up reminders/tasks, notes, call log, email log, full attribution tab, timeline of all touchpoints (pages visited before converting, events, chat transcript).
- One-click actions: call, WhatsApp (prefilled template), email template, convert to repair job, mark lost.
- Duplicate detection by phone/email; merge customers.
- Saved filters/segments, CSV export, marketing-consent-aware email lists.

### Repair jobs (workshop management)
- Status flow: Booked → Collection Scheduled → Collected → Diagnosing → Awaiting Approval (if quote changes) → Awaiting Parts → Repairing → Quality Check → Ready for Return → Out for Delivery → Returned → Completed/Paid; plus Cancelled / Unrepairable.
- Each status change can notify the customer (email + optional SMS via Twilio/Vonage when configured) and updates `/track-repair`.
- Collection checklist (screen, buttons, cameras, Face ID/Touch ID, speakers, charging, water indicators, cosmetic condition) with photos at collection and return; customer e-signature on collection and return.
- Customer approval link for revised quotes (approve/decline online, logged).
- Printable/PDF job sheet with QR code, receipt and invoice.
- Warranty tracking and warranty-claim jobs linked to the original job.
- Parts used deducted from inventory automatically.

### Other admin resources
- Device models, repair types, **price matrix grid** (bulk edit, CSV import/export, % uplift action), service areas (map preview), availability calendar, inventory & suppliers, quotes/invoices/payments, customers, pages (block builder), blog, videos, FAQs, reviews, redirects, 404 log, menu builder, settings (business info, NAP, hours, social links, tracking IDs, API keys, feature flags, email/SMS templates, chatbot settings), consent log, tracking events, user management.
- Global search across customers, phone numbers, job references, IMEIs.
- Reports page with date-range filters and export.

### eBay link (future-ready)
- Shared inventory: accessory stock items have a flag "listed on eBay" with eBay item ID/URL; manual stock sync now, with a service class ready for eBay Sell Inventory API sync later. Link to the eBay store in the footer/shop when enabled.

---

## 13. AI chatbot (Laravel AI SDK) + MCP server

### Website chatbot
- Floating, branded chat widget (Livewire, lazy-loaded after interaction or idle so it doesn't hurt CWV). Clearly labelled as an AI assistant with an option to "talk to a human" (WhatsApp/call/callback).
- Built as a **Laravel AI SDK agent** with a system prompt generated from Settings (business info, service areas, policies, opening hours) and **tools** backed by real data:
  - `CheckPostcodeCoverage(postcode)`
  - `GetRepairPrice(model, repair_type)` / `ListRepairsForModel(model)`
  - `GetAvailableCollectionSlots(postcode, date?)`
  - `CreateLead(name, phone, email, model, issue, postcode, preferred_slot)` — creates a Lead with channel=Chatbot **and** the visitor's stored attribution (gclid/fbclid/UTMs), then fires the conversion events
  - `StartBooking(prefill)` — returns a deep link into the booking wizard prefilled
  - `GetJobStatus(reference, postcode)`
  - `SearchHelpArticles(query)` — over blog posts/FAQs (use Laravel 13 vector/semantic search with embeddings if available on the DB; otherwise full-text)
- Guardrails: stays on topic (ZR services, phone care), never invents prices (only from the tool), never asks for passcodes/card details, rate-limited per IP/session, max message length, profanity/abuse handling, graceful fallback if the AI provider is down (show WhatsApp/call options).
- Provider-agnostic (Anthropic/OpenAI/Gemini) — provider, model and API key set in Settings/env.
- All conversations stored (linked to lead/customer when identified), viewable in Filament with a human takeover/reply option; retention period setting (UK GDPR) with automatic pruning.

### MCP server (laravel/mcp)
- Expose an authenticated MCP server (Sanctum/OAuth token, admin-issued, scoped) so the owner can use Claude or another MCP client to work the business:
  - Tools: `search_leads`, `get_lead`, `update_lead_status`, `list_todays_jobs`, `update_job_status`, `get_dashboard_stats(period)`, `get_repair_prices`, `update_repair_price`, `create_blog_draft`, `list_low_stock`.
  - Resources: business settings, price list, service areas.
- Every MCP action logged in the activity log under the token's user; destructive actions require explicit confirmation parameters.

---

## 14. Notifications and communications

- Branded Markdown mail templates (logo, palette): booking confirmation, collection reminder (day before + morning of), status updates, quote approval, ready for return, completed + review request, warranty reminder, abandoned booking follow-up (only with consent where required).
- Admin alerts on new lead/booking via email + configurable webhook (WhatsApp Business API / Telegram / Slack) — build a `NotificationChannel` abstraction so channels can be added later.
- SMS via Twilio/Vonage (optional, switchable).
- All templates editable in Filament.
- Transactional mail via a proper provider (Postmark/SES/Mailgun) with SPF, DKIM and DMARC documented in the deploy README for `zrtechnologiesltd.com`.

---

## 15. UK legal and trust requirements

- **Privacy policy** (UK GDPR, data controller ZR TECHNOLOGIES LTD, lawful bases, retention, rights, ICO complaint route), **cookie policy** listing actual cookies, **terms & conditions**, **repair terms** (diagnosis, quotes, data/backup disclaimer, Activation Lock, uncollected items, liability, warranty terms), **accessibility statement**. Generate solid drafts marked "to be reviewed by the owner/legal adviser".
- Consumer Contracts Regulations: explain cancellation rights before repair work starts and the customer's request to start work within the cancellation period.
- Footer company details: registered name, company number 17589309, registered office, registered in England & Wales.
- **Trademark disclaimer:** "ZR Technologies is an independent repair provider and is not affiliated with or endorsed by Apple Inc. iPhone is a trademark of Apple Inc." No Apple logos.
- **Reviews:** only display genuine reviews (UK DMCC Act 2024 bans fake reviews). Seeders may create clearly-labelled sample reviews **only in local/dev environments**; production starts empty with a "Leave a review" CTA.
- Data subject tools in admin: export a customer's data, anonymise/delete a customer.
- Placeholder fields for ICO registration number and VAT number (shown only when filled).

---

## 16. Security and performance

- HTTPS, HSTS, CSP (with nonces for tag scripts), secure cookies, rate limiting on all forms and the chatbot, Turnstile + honeypot on public forms, signed URLs for customer quote/track links, encrypted columns for sensitive data (e.g. IMEI where appropriate), file-upload validation and private storage for job photos.
- Queues for everything slow (emails, CAPI/Ads/GA4 sends, image conversions, sitemap regeneration).
- Full-page response cache for public pages, cache busting on save, eager loading (no N+1 — add `Model::preventLazyLoading()` in non-prod), DB indexes on all lookup columns (slugs, phone, email, reference, gclid, status).
- Daily backups off-site, error tracking (Sentry or Nightwatch), uptime monitoring, health check route.

---

## 17. Seed data and content

- Settings seeded with all business facts from section 1.
- Device models (iPhone 8/SE onward to current), repair types, a full price matrix with **draft placeholder prices clearly flagged "DRAFT – confirm before launch"** in the admin.
- Service areas listed in section 4 (draft, owner confirms).
- Pages with real, well-written, conversion-focused UK English copy (not lorem ipsum) for home, iPhone repair pillar, each repair pillar, how it works, about, FAQs (20+ real questions), business, warranty, legal drafts.
- Draft blog posts and video placeholders from sections 10–11.
- Demo staff users per role (dev only).

### Home page sections (in order)
1. Hero: "Expert iPhone Repair in Blackburn — We Collect, Repair & Return" + postcode checker + "Get an instant price" + trust badges (warranty, collection & delivery, fast turnaround, quality parts)
2. Instant price checker (model → repair → price → Book)
3. How it works (4 steps with icons)
4. Popular repairs (cards with "from £" prices pulled from the DB)
5. Why ZR Technologies (benefits)
6. Video shorts carousel
7. Areas we cover (map + town links)
8. Reviews (genuine only; Google rating badge when connected)
9. Latest guides from the blog
10. FAQs (FAQPage schema)
11. Final CTA band (gradient) with Call / WhatsApp / Book
12. "Coming soon" teaser for accessories & custom printing (feature-flagged)

---

## 18. Phased delivery plan

- **Phase 0 — Plan:** architecture, ERD, package list, route map, design tokens. Wait for approval.
- **Phase 1 — Foundation:** Laravel 13 install, Filament v5 panel with brand theme, auth + roles + 2FA, Settings, media library, base layout, design system components (Blade components for buttons, cards, sections, forms), header/footer/mobile sticky bar.
- **Phase 2 — Catalogue & pricing:** models, repair types, price matrix, service areas, availability; price checker; postcode checker.
- **Phase 3 — Booking & CRM:** booking wizard, leads, customers, repair jobs, statuses, notifications, track-repair page, PDFs.
- **Phase 4 — Content & SEO:** pages/block builder, pillar/model/location pages, blog, videos, FAQs, reviews, schema, sitemaps, robots, redirects, 404 log, breadcrumbs, internal linking.
- **Phase 5 — Tracking:** consent banner + Consent Mode v2, attribution capture, dataLayer, GA4/Ads/Meta browser events, Meta CAPI, enhanced conversions, offline conversion uploads, Measurement Protocol, tracking health page, GTM export + docs.
- **Phase 6 — AI:** chatbot agent + tools + admin inbox, blog AI assistant, MCP server.
- **Phase 7 — Dashboard & reports:** KPI widgets, charts, reports, today's route.
- **Phase 8 — Hardening:** tests, Lighthouse/CWV pass, accessibility audit, security headers, backups, monitoring, legal pages, deploy docs.
- **Phase 9 (later, flagged):** accessories shop (cart + Stripe checkout), custom printing quote flow, web-development/IT services pages, eBay inventory sync.

---

## 19. Definition of done

- Every page in section 4 renders with real copy, correct schema (passes Rich Results Test), unique title/description and correct canonical.
- A test booking from a URL with `?gclid=TEST123&utm_source=google&utm_medium=cpc&utm_campaign=test` creates a Lead showing those values in the CRM, fires GA4 + Google Ads + Meta browser events, sends a deduplicated Meta CAPI event, and — when the job is set to Completed — queues an offline conversion with the GCLID and real value. All visible in Tracking Health.
- Same test with `?fbclid=...` correctly builds `fbc` and attributes to Meta Ads; a visit via the GBP UTM link attributes to Google Business Profile.
- Rejecting cookies prevents marketing/analytics tags from setting cookies (verify in DevTools) while consent-mode pings behave as configured.
- Chatbot can quote a real price, check a postcode, offer slots and create an attributed lead.
- MCP server responds to an authenticated client and lists today's jobs.
- Lighthouse mobile ≥ 95 on key templates; no console errors; WCAG 2.2 AA on forms and navigation.
- `php artisan test` green; Larastan passes; README covers local setup, env variables, queues/scheduler, deployment (Forge/Ploi/Laravel Cloud or cPanel/VPS), DNS + email authentication, GTM import, Google Ads/Meta setup steps and how to add a new service vertical.
