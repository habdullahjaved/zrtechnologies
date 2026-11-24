/**
 * Consent-aware tracking.
 *
 * Google Consent Mode defaults are set in <head> (components/site/tracking-head).
 * This module:
 *  - loads Google's tag (or Google Tag Manager) straight away, which only
 *    sets cookies once the visitor consents (Consent Mode v2);
 *  - loads the Meta Pixel, TikTok Pixel and Microsoft Clarity only after the
 *    matching consent;
 *  - sends website events through one function, zrTrack(), which pushes to
 *    the dataLayer (for GTM) and, without GTM, calls Google and Meta directly.
 *
 * The dataLayer event contract is documented in docs/TRACKING.md.
 */

const config = window.zrTracking || {};
const loaded = new Set();

function loadScript(key, src) {
    if (loaded.has(key)) {
        return;
    }

    loaded.add(key);
    const script = document.createElement('script');
    script.async = true;
    script.src = src;
    document.head.appendChild(script);
}

function loadGoogle() {
    if (config.gtm) {
        if (!loaded.has('gtm')) {
            window.dataLayer.push({ 'gtm.start': Date.now(), event: 'gtm.js' });
        }
        loadScript('gtm', `https://www.googletagmanager.com/gtm.js?id=${encodeURIComponent(config.gtm)}`);

        return;
    }

    const primaryId = config.ga4 || config.ads?.id;

    if (!primaryId || loaded.has('gtag')) {
        return;
    }

    loadScript('gtag', `https://www.googletagmanager.com/gtag/js?id=${encodeURIComponent(primaryId)}`);
    window.gtag('js', new Date());

    if (config.ga4) {
        window.gtag('config', config.ga4, config.testMode ? { debug_mode: true } : {});
    }

    if (config.ads?.id) {
        window.gtag('config', config.ads.id, { allow_enhanced_conversions: !!config.enhancedConversions });
    }
}

function loadMetaPixel() {
    if (!config.meta || window.fbq) {
        return;
    }

    const fbq = function () {
        fbq.callMethod ? fbq.callMethod.apply(fbq, arguments) : fbq.queue.push(arguments);
    };
    fbq.push = fbq;
    fbq.loaded = true;
    fbq.version = '2.0';
    fbq.queue = [];
    window.fbq = fbq;
    window._fbq = fbq;

    loadScript('meta', 'https://connect.facebook.net/en_GB/fbevents.js');
    window.fbq('consent', 'grant');
    window.fbq('init', config.meta);
    window.fbq('track', 'PageView');
}

function loadTikTok() {
    if (!config.tiktok || window.ttq) {
        return;
    }

    const ttq = [];
    ttq.methods = ['page', 'track', 'identify', 'instances', 'debug', 'on', 'off', 'once', 'ready', 'alias', 'group', 'enableCookie', 'disableCookie'];
    ttq.setAndDefer = (target, method) => {
        target[method] = (...args) => target.push([method, ...args]);
    };
    ttq.methods.forEach((method) => ttq.setAndDefer(ttq, method));
    window.ttq = ttq;

    loadScript('tiktok', `https://analytics.tiktok.com/i18n/pixel/events.js?sdkid=${encodeURIComponent(config.tiktok)}&lib=ttq`);
    window.ttq.page();
}

function loadClarity() {
    if (!config.clarity || window.clarity) {
        return;
    }

    window.clarity = function () {
        (window.clarity.q = window.clarity.q || []).push(arguments);
    };
    loadScript('clarity', `https://www.clarity.ms/tag/${encodeURIComponent(config.clarity)}`);
    window.clarity('consent');
}

/**
 * Apply a consent choice: update Google Consent Mode and load the tags it allows.
 */
export function applyConsent(consent) {
    window.zrConsent = consent;

    window.gtag('consent', 'update', {
        analytics_storage: consent.analytics ? 'granted' : 'denied',
        ad_storage: consent.marketing ? 'granted' : 'denied',
        ad_user_data: consent.marketing ? 'granted' : 'denied',
        ad_personalization: consent.marketing ? 'granted' : 'denied',
    });
    window.dataLayer.push({ event: 'consent_update', analytics_consent: consent.analytics, marketing_consent: consent.marketing });

    if (consent.marketing) {
        loadMetaPixel();
        loadTikTok();
    } else if (window.fbq) {
        window.fbq('consent', 'revoke');
    }

    if (consent.analytics) {
        loadClarity();
    }
}

/** Meta standard events for each website event. */
const META_EVENTS = {
    book_repair: ['Lead', 'Schedule'],
    generate_lead: ['Lead'],
    click_to_call: ['Contact'],
    click_whatsapp: ['Contact'],
    booking_step_1: ['InitiateCheckout'],
    view_price: ['ViewContent'],
};

/**
 * Send a website event. Events with an event_id are sent once per browser
 * session (a reloaded thank-you page does not count twice), and the same
 * event_id is used server-side so Meta deduplicates the two.
 */
export function track(name, params = {}) {
    if (params.event_id) {
        const key = `zr_event_${name}_${params.event_id}`;

        try {
            if (window.sessionStorage.getItem(key)) {
                return;
            }
            window.sessionStorage.setItem(key, '1');
        } catch {
            // Private browsing without storage: send anyway.
        }
    }

    const { user_data: userData, ...eventParams } = params;
    const sendUserData = config.enhancedConversions && userData;

    window.dataLayer.push({ event: name, ...eventParams, ...(sendUserData ? { user_data: userData } : {}) });

    if (!config.gtm && (config.ga4 || config.ads?.id)) {
        if (config.ga4) {
            window.gtag('event', name, eventParams);
        }

        const sendTo = config.ads?.sendTo?.[name];

        if (sendTo) {
            if (sendUserData) {
                window.gtag('set', 'user_data', userData);
            }

            window.gtag('event', 'conversion', {
                send_to: sendTo,
                value: eventParams.value,
                currency: eventParams.currency || 'GBP',
                transaction_id: eventParams.event_id,
            });
        }
    }

    if (window.fbq && window.zrConsent?.marketing && META_EVENTS[name]) {
        const metaEvents = name === 'generate_lead' && eventParams.lead_type === 'contact' ? ['Contact'] : META_EVENTS[name];
        const data = eventParams.value ? { value: eventParams.value, currency: eventParams.currency || 'GBP' } : {};

        metaEvents.forEach((metaEvent) => window.fbq('track', metaEvent, data, eventParams.event_id ? { eventID: eventParams.event_id } : {}));
    }
}

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

function uuid() {
    return window.crypto?.randomUUID ? window.crypto.randomUUID() : `${Date.now()}-${Math.random().toString(16).slice(2)}`.padEnd(36, '0');
}

/**
 * Taps on phone and WhatsApp links ([data-track]): tracked in the browser and
 * reported to the server (logged as a lead, and sent to Meta with the same event_id).
 */
function trackClicks() {
    document.addEventListener('click', (event) => {
        const link = event.target.closest?.('[data-track]');

        if (!link) {
            return;
        }

        const name = link.dataset.track;
        const eventId = uuid();
        const location = link.dataset.trackLocation || link.closest('header, footer, nav, main section')?.tagName.toLowerCase() || 'page';

        track(name, { event_id: eventId, link_location: location });

        if (config.eventUrl && /^[0-9a-f-]{36}$/i.test(eventId)) {
            fetch(config.eventUrl, {
                method: 'POST',
                keepalive: true,
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken() },
                body: JSON.stringify({ event: name, event_id: eventId, location }),
            }).catch(() => {});
        }
    });
}

/**
 * Events rendered into the page by the server, e.g. a completed booking on
 * the thank-you page: <div data-zr-event='{"name": "...", "params": {...}}'>.
 */
function trackRenderedEvents() {
    document.querySelectorAll('[data-zr-event]').forEach((element) => {
        try {
            const { name, params } = JSON.parse(element.dataset.zrEvent);
            track(name, params || {});
        } catch {
            // Ignore malformed data.
        }
    });
}

/**
 * The cookie banner (components/site/cookie-banner).
 */
export function cookieBanner() {
    return {
        open: false,
        showPreferences: false,
        analytics: false,
        marketing: false,

        init() {
            if (window.zrConsent) {
                this.analytics = window.zrConsent.analytics;
                this.marketing = window.zrConsent.marketing;
            } else {
                this.open = true;
            }
        },

        openPreferences() {
            this.open = true;
            this.showPreferences = true;
        },

        acceptAll() {
            this.save('accept_all', true, true);
        },

        rejectAll() {
            this.save('reject_all', false, false);
        },

        saveChoices() {
            this.save('custom', this.analytics, this.marketing);
        },

        save(action, analytics, marketing) {
            this.analytics = analytics;
            this.marketing = marketing;
            this.open = false;
            this.showPreferences = false;
            applyConsent({ analytics, marketing });

            fetch(config.consentUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken() },
                body: JSON.stringify({ action, analytics, marketing, page: window.location.pathname }),
            }).catch(() => {});
        },
    };
}

export function startTracking() {
    window.zrTrack = track;

    loadGoogle();

    if (window.zrConsent) {
        applyConsent(window.zrConsent);
    }

    trackClicks();
    trackRenderedEvents();

    // Livewire components: $this->dispatch('zr-track', name: '…', params: [...]).
    window.addEventListener('zr-track', (event) => track(event.detail?.name, event.detail?.params || {}));
}
