/**
 * Public site JavaScript.
 *
 * Livewire and Alpine are bundled once from Livewire's ESM build (the layout
 * uses @livewireScriptConfig instead of @livewireScripts), so later Livewire
 * components and these Alpine helpers share one Alpine instance.
 */
import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';
import { cookieBanner, startTracking } from './tracking';

/**
 * Mobile navigation drawer: traps focus loosely, closes on Escape and locks page scroll.
 */
Alpine.data('mobileNav', () => ({
    open: false,

    toggle() {
        this.open ? this.close() : this.show();
    },

    show() {
        this.open = true;
        document.documentElement.classList.add('overflow-hidden');
        this.$nextTick(() => this.$refs.panel?.querySelector('a, button')?.focus());
    },

    close() {
        if (!this.open) {
            return;
        }

        this.open = false;
        document.documentElement.classList.remove('overflow-hidden');
        this.$refs.toggle?.focus();
    },
}));

/**
 * Header shadow once the page has scrolled.
 */
Alpine.data('stickyHeader', () => ({
    scrolled: false,

    init() {
        const update = () => (this.scrolled = window.scrollY > 8);
        update();
        window.addEventListener('scroll', update, { passive: true });
    },
}));

/**
 * Desktop "iPhone Repair" mega menu: opens on click or hover, closes on
 * Escape, outside click or when focus leaves it.
 */
Alpine.data('megaMenu', () => ({
    open: false,
    hoverTimer: null,

    toggle() {
        this.open = !this.open;
    },

    enter() {
        clearTimeout(this.hoverTimer);
        this.open = true;
    },

    leave() {
        clearTimeout(this.hoverTimer);
        this.hoverTimer = setTimeout(() => (this.open = false), 150);
    },

    close(focusButton = false) {
        this.open = false;

        if (focusButton) {
            this.$refs.button?.focus();
        }
    },

    focusOut(event) {
        if (!this.$el.contains(event.relatedTarget)) {
            this.open = false;
        }
    },
}));

/**
 * Click-to-load embeds (videos, maps). Nothing from the third party loads
 * until the visitor asks for it, which keeps pages fast and private.
 */
Alpine.data('embedFacade', () => ({
    loaded: false,

    load() {
        this.loaded = true;
        this.$nextTick(() => this.$el.querySelector('iframe, video')?.focus());
    },
}));

/**
 * "Copy link" share button.
 */
Alpine.data('copyLink', (url) => ({
    copied: false,

    async copy() {
        try {
            await navigator.clipboard.writeText(url);
            this.copied = true;
            setTimeout(() => (this.copied = false), 2500);
        } catch {
            window.prompt('Copy this link', url);
        }
    },
}));

Alpine.data('cookieBanner', cookieBanner);

startTracking();
Livewire.start();
