import { router } from '@inertiajs/vue3';

declare global {
    interface Window {
        plausible?: (...args: unknown[]) => void;
    }
}

type PlausibleConfig = {
    domain: string;
    src: string;
};

let loaded = false;
let inertiaListenerBound = false;

export function loadPlausible(config: PlausibleConfig): void {
    if (loaded || typeof document === 'undefined') {
        return;
    }

    loaded = true;

    const script = document.createElement('script');
    script.defer = true;
    script.setAttribute('data-domain', config.domain);
    script.src = config.src;
    document.head.appendChild(script);

    // Queue pageviews until the real script boots
    window.plausible =
        window.plausible ||
        function (...args: unknown[]) {
            (window.plausible!.q = window.plausible!.q || []).push(args);
        };

    if (!inertiaListenerBound) {
        inertiaListenerBound = true;
        router.on('navigate', () => {
            window.plausible?.('pageview');
        });
    }
}

export function unloadPlausible(): void {
    // Plausible's script has no unload API. Scripts already loaded stay until
    // the next full page navigation. We null out the queue so nothing new is
    // tracked after revocation. The user can hard-reload for a clean state.
    if (typeof window !== 'undefined') {
        window.plausible = function () {
            /* noop after revocation */
        };
    }

    loaded = false;
}
