# Tracking and conversions

How the website measures enquiries, bookings and completed repairs, and how to connect Google Analytics 4, Google Ads and Meta. Every ID is entered in **Admin → Settings → Tracking**; nothing is hard-coded.

## Consent comes first

- Visitors see a cookie banner with **Accept all** and **Reject all** (equally prominent) and **Manage preferences** (analytics / marketing). Each choice is logged in **Marketing → Consent log**.
- **Google Consent Mode v2** starts with everything `denied`. Google tags load but set no cookies until the visitor accepts (cookieless pings only).
- **Meta Pixel, TikTok** load only after **marketing** consent; **Microsoft Clarity** only after **analytics** consent.
- Server-side sends check the consent stored on the lead: no marketing consent, no Meta or Google Ads conversion.
- Changing the policy version (Settings → Cookie banner) asks every visitor again.

## Two ways to run the tags

| | With Google Tag Manager | Without (default) |
|---|---|---|
| Setting | GTM container ID filled in | GTM container ID empty |
| Google Analytics 4, Google Ads | Your GTM tags (start from `docs/gtm-container.json`) | Loaded by the website |
| Meta Pixel, TikTok, Clarity | Loaded by the website in both cases | Loaded by the website |

Do not add GA4 or Google Ads tags in GTM *and* leave the website loading them: with a container ID the website stops loading them itself.

## Events (the dataLayer contract)

Every event is pushed to `window.dataLayer` and, without GTM, sent straight to GA4, Google Ads and Meta.

| Event | When | Parameters | Google Ads | Meta |
|---|---|---|---|---|
| `book_repair` | Booking confirmed (thank-you page) | `event_id`, `reference`, `value`, `currency`, `device`, `repairs`, `user_data` | "booking" label | `Lead` + `Schedule` |
| `generate_lead` | Quote, callback, contact, business and outside-area forms | `event_id`, `lead_type`, `user_data` | "lead" label | `Lead` (`Contact` for the contact form) |
| `click_to_call` | Phone number tapped | `event_id`, `link_location` | "call" label | `Contact` |
| `click_whatsapp` | WhatsApp button tapped | `event_id`, `link_location` | "WhatsApp" label | `Contact` |
| `booking_step_1` … `booking_step_6` | Each booking step shown | `step` | | `InitiateCheckout` (step 1) |
| `view_price` | Model chosen in the price checker | `model` | | `ViewContent` |
| `consent_update` | Cookie choice made | `analytics_consent`, `marketing_consent` | | |

`user_data` holds the customer's `email` and `phone_number`. It is only included when **enhanced conversions** is on; Google hashes it in the browser before sending.

In GA4, mark `book_repair` and `generate_lead` as **key events** (Admin → Events).

## Server-side conversions

Sent from the server by queued jobs (the cron job runs them within a minute), logged in **Marketing → Tracking events** and retried up to five times.

| Platform | Sent | Notes |
|---|---|---|
| Meta Conversions API | Every website lead and booking; `Purchase` when a repair is completed | Same `event_id` as the browser Pixel, so Meta counts each once. Email, phone, name and postcode are SHA-256 hashed. |
| GA4 Measurement Protocol | `purchase` when a repair is completed | Uses the visitor's GA client ID. Website leads are not re-sent (GA4 cannot de-duplicate). |
| Google Ads (offline import) | Completed repairs | Added to a CSV that Google Ads collects daily, with the gclid / gbraid / wbraid, or hashed email and phone (enhanced conversions for leads). |

### Google Ads offline conversions

1. Google Ads → Goals → Conversions → **New conversion action → Import → CRM, files or other data sources → Track conversions from clicks**. Name it exactly as in Settings → Tracking (default **Repair Completed**), value "Use different values".
2. Goals → Conversions → **Uploads → Schedules → + New schedule**, source **HTTPS**, and paste the link from Marketing → Tracking health. Daily is fine. Rows Google has already imported are ignored.

## Setting up each platform

**Google Analytics 4:** Admin → Data streams → Web → copy the **Measurement ID** (`G-…`). For completed-repair purchases also create a **Measurement Protocol API secret** on the same stream.

**Google Ads:** Goals → Conversions → create website conversion actions for *Booking*, *Lead form*, *Phone call clicks* and *WhatsApp clicks* ("Use Google tag" → "Set up manually"). Copy the **Conversion ID** (`AW-…`) once and each action's **label**. Switch on enhanced conversions in Google Ads (Settings → Enhanced conversions for web → "Google tag").

**Meta:** Events Manager → your dataset (Pixel) → copy the **ID**. Settings → Conversions API → **Generate access token**. To test, copy the **Test events** code, switch on test mode, place a test enquiry, check it in Test events, then switch test mode off.

**Microsoft Clarity:** Settings → Overview → **Project ID**.

## Checking it works

1. **Marketing → Tracking health:** every platform you use shows a tick; use **Send Meta test event** and **Validate GA4 server event**.
2. Open the site with `?gclid=TEST123&utm_source=google&utm_medium=cpc&utm_campaign=test`, accept cookies and send an enquiry. The lead's **Attribution** tab shows the gclid and UTMs, and **Tracking events** shows the Meta send.
3. With `?fbclid=abc123` the lead shows an `fbc` value (`fb.1.….abc123`) and the channel **Meta ads**.
4. With `?utm_source=google&utm_medium=organic&utm_campaign=gbp` the channel is **Google Business Profile** (use the ready-made links on Tracking health in your profile).
5. Reject cookies in a private window: DevTools → Application → Cookies shows no `_ga`, `_gcl_*`, `_fbp` or Clarity cookies.

## Google Tag Manager container

`docs/gtm-container.json` contains a starting container: a Google tag for GA4, GA4 event tags, Google Ads conversion tags and the conversion linker, with triggers and dataLayer variables for every event above.

1. GTM → Admin → **Import container** → choose the file → **Merge** (or Overwrite on a new container).
2. Variables → edit the constants **GA4 Measurement ID**, **Google Ads Conversion ID** (digits only, without `AW-`) and the four **Google Ads label** constants.
3. Preview with Tag Assistant, then **Submit**.
4. Paste the container ID into Settings → Tracking.

Meta, TikTok and Clarity stay with the website (they only load after consent), so do not add them in GTM.
