# Deploying to Hostinger (shared hosting)

This guide puts the site live at **https://zrtechnologiesltd.com** on a Hostinger Premium or Business plan (SSH and cron are needed). Follow it top to bottom the first time; later updates only need [Updating the site](#updating-the-site).

## 1. In hPanel

1. **PHP version:** Websites → your site → Advanced → PHP Configuration → **PHP 8.5** (8.4.1 is the minimum). Extensions: `curl`, `exif`, `fileinfo`, `gd`, `intl`, `mbstring`, `openssl`, `pdo_mysql`, `sodium`, `zip`, `opcache`.
2. **Database:** Databases → MySQL → create a database and user. Note the name, user and password (the host is usually `localhost`).
3. **Email:** Emails → create `mrumar@zrtechnologiesltd.com` (or a `no-reply@` box) and note its password.
4. **SSH:** Advanced → SSH Access → enable it and note the host, port and username.
5. **SSL:** Security → SSL → make sure the free certificate is active for `zrtechnologiesltd.com` and `www.zrtechnologiesltd.com`.

## 2. Get the code from GitHub

The code lives in **https://github.com/habdullahjaved/zrtechnologies** (built CSS/JS included, because shared hosting has no Node.js). It goes **outside** `public_html`, so `.env`, `storage` and the code can never be downloaded; only the `public` folder is web-facing.

**a. Give the server read access to the repository (once).** Over SSH:

```bash
ssh -p PORT USER@HOST
ssh-keygen -t ed25519 -C "hostinger-zrtechnologies" -f ~/.ssh/github_zr -N ""
cat ~/.ssh/github_zr.pub
printf 'Host github.com\n  IdentityFile ~/.ssh/github_zr\n  IdentitiesOnly yes\n' >> ~/.ssh/config
```

Copy the line `cat` printed. On GitHub: the repository → **Settings → Deploy keys → Add deploy key**, paste it, leave "Allow write access" **off**, save.

**b. Clone and install:**

```bash
cd ~/domains/zrtechnologiesltd.com
git clone git@github.com:habdullahjaved/zrtechnologies.git app
cd app
php -v                                              # must say 8.4 or 8.5; if not, change it in hPanel and reconnect
composer2 install --no-dev --optimize-autoloader   # Hostinger's "composer" is v1; use composer2
```

(hPanel also has **Advanced → Git**, which can clone the same repository into `domains/zrtechnologiesltd.com/app`; you still need SSH for the commands below.)

No SSH or GitHub? Upload `deploy/release/zrtechnologies-upload.zip` with File Manager into a folder named `app`, extract it, and run the `composer2` line from hPanel's terminal.

## 3. Point the website at `public`

```bash
cd ~/domains/zrtechnologiesltd.com
mv public_html public_html.old                  # keep Hostinger's placeholder, just in case
ln -s ~/domains/zrtechnologiesltd.com/app/public public_html
```

## 4. Configure `.env`

```bash
cd ~/domains/zrtechnologiesltd.com/app
cp .env.example .env
php artisan key:generate
nano .env
```

| Setting | Value |
|---|---|
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_URL` | `https://zrtechnologiesltd.com` |
| `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | from step 1 (port 3306) |
| `MAIL_MAILER` | `smtp` |
| `MAIL_SCHEME` / `MAIL_HOST` / `MAIL_PORT` | `smtps` / `smtp.hostinger.com` / `465` |
| `MAIL_USERNAME`, `MAIL_PASSWORD` | the mailbox from step 1 |
| `MAIL_FROM_ADDRESS` | the same mailbox |
| `ADMIN_EMAIL`, `ADMIN_NAME`, `ADMIN_PASSWORD` | the owner's account (the password is only used the first time) |
| `ADMIN_REQUIRE_MFA` | `true` |
| `QUEUE_CONNECTION` | `database` |
| `SESSION_SECURE_COOKIE` | `true` |

## 5. Set up the database and caches

```bash
php artisan migrate --force
php artisan db:seed --force          # roles, owner account, draft catalogue, content (never demo data in production)
php artisan storage:link
php artisan settings:clear-cache
php artisan optimize
```

## 6. Cron (queue and scheduler)

hPanel → Advanced → Cron Jobs → **every minute**:

```
/usr/bin/php /home/USER/domains/zrtechnologiesltd.com/app/artisan schedule:run >> /dev/null 2>&1
```

This sends queued emails and conversions within about a minute, publishes scheduled posts and keeps the logs tidy. `deploy/cron.sh` does the same if Hostinger asks for a script.

## 7. DNS for email (SPF, DKIM, DMARC)

In hPanel → Domains → DNS (or wherever the domain's DNS is managed):

| Type | Name | Value |
|---|---|---|
| TXT | `@` | `v=spf1 include:_spf.mail.hostinger.com ~all` (only one SPF record; merge if one exists) |
| CNAME/TXT | DKIM | Emails → your domain → **DNS records**: copy the DKIM records Hostinger shows |
| TXT | `_dmarc` | `v=DMARC1; p=quarantine; rua=mailto:mrumar@zrtechnologiesltd.com; fo=1` |

Start DMARC with `p=none` for a week if you want to watch reports first. Send a test email from the admin (place a test booking) and check it does not land in spam.

## 8. Before you announce the site

```bash
php artisan launch:check
```

It must show no ✗. Then, in the admin:

1. **Catalogue → Price list:** confirm every price (clears "DRAFT").
2. **Coverage → Service areas:** confirm districts and fees, switch off "Draft".
3. **Content → Pages:** have the legal pages reviewed, then untick "Needs legal review".
4. **Settings → Tracking:** add the GA4, Google Ads and Meta IDs (see `docs/TRACKING.md`). Check **Marketing → Tracking health**.
5. **Settings → Social:** add the Google Business Profile and review links.
6. **Settings → Website & SEO:** switch on "Allow search engines to index the website".
7. Place a **test booking** from `https://zrtechnologiesltd.com/?gclid=TEST123&utm_source=google&utm_medium=cpc&utm_campaign=test`, accept cookies, and check the lead in **CRM → Leads → Attribution** and in **Marketing → Tracking events**. Then cancel it.
8. Submit `https://zrtechnologiesltd.com/sitemap.xml` in Google Search Console and Bing Webmaster Tools.

## Updating the site

```bash
# on your computer
npm run build
git add -A && git commit -m "Describe the change" && git push

# on the server
cd ~/domains/zrtechnologiesltd.com/app
bash deploy/deploy.sh
```

`deploy/deploy.sh` pulls the code, installs packages, runs migrations and refreshes the caches (including the page cache and `settings:clear-cache`, which new settings need).

## If something goes wrong

- **"500 Server Error":** check `storage/logs/laravel.log`. Make sure `storage` and `bootstrap/cache` are writable (`chmod -R 775 storage bootstrap/cache`).
- **Old page still showing:** `php artisan cache:clear` (pages are cached for visitors and normally clear themselves when you save).
- **Emails not arriving:** check the cron job is running (`php artisan launch:check`) and the SMTP details.
