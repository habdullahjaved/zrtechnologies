/**
 * Public site JavaScript.
 *
 * Livewire and Alpine are bundled once from Livewire's ESM build (the layout
 * uses @livewireScriptConfig instead of @livewireScripts), so later Livewire
 * components and these Alpine helpers share one Alpine instance.
 */
import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';

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

Livewire.start();
