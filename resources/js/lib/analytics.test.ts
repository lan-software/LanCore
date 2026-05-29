import { beforeEach, describe, expect, it, vi } from 'vitest';
import type * as AnalyticsModule from './analytics';

const routerOn = vi.fn();

vi.mock('@inertiajs/vue3', () => ({
    router: {
        on: (...args: unknown[]) => routerOn(...args),
    },
}));

const config = {
    domain: 'lan.test',
    src: 'https://plausible.test/js/script.js',
};

let analytics: typeof AnalyticsModule;

beforeEach(async () => {
    // Fresh module state each test — `loaded` / `inertiaListenerBound` are
    // module-level singletons that otherwise leak between tests.
    vi.resetModules();
    routerOn.mockClear();
    document.head.innerHTML = '';
    delete (window as { plausible?: unknown }).plausible;
    analytics = await import('./analytics');
});

describe('loadPlausible', () => {
    it('injects a deferred script tag with the data-domain and src', () => {
        analytics.loadPlausible(config);

        const script = document.head.querySelector('script');
        expect(script).not.toBeNull();
        expect(script!.defer).toBe(true);
        expect(script!.getAttribute('data-domain')).toBe('lan.test');
        expect(script!.src).toContain('plausible.test');
    });

    it('installs a queueing plausible stub that buffers calls', () => {
        analytics.loadPlausible(config);

        expect(typeof window.plausible).toBe('function');
        window.plausible!('pageview');
        expect(
            (window.plausible as unknown as { q: unknown[] }).q,
        ).toContainEqual(['pageview']);
    });

    it('binds a single Inertia navigate listener that fires a pageview', () => {
        analytics.loadPlausible(config);
        expect(routerOn).toHaveBeenCalledTimes(1);
        expect(routerOn).toHaveBeenCalledWith('navigate', expect.any(Function));

        const handler = routerOn.mock.calls[0][1] as () => void;
        const spy = vi.fn();
        window.plausible = spy;
        handler();
        expect(spy).toHaveBeenCalledWith('pageview');
    });

    it('is idempotent — a second call injects no second script', () => {
        analytics.loadPlausible(config);
        analytics.loadPlausible(config);
        expect(document.head.querySelectorAll('script')).toHaveLength(1);
    });
});

describe('unloadPlausible', () => {
    it('replaces plausible with a no-op so later tracking is dropped', () => {
        analytics.loadPlausible(config);
        analytics.unloadPlausible();

        expect(typeof window.plausible).toBe('function');
        expect(() => window.plausible!('pageview')).not.toThrow();
        expect(
            (window.plausible as unknown as { q?: unknown[] }).q,
        ).toBeUndefined();
    });

    it('lets loadPlausible inject again after an unload', () => {
        analytics.loadPlausible(config);
        analytics.unloadPlausible();
        document.head.innerHTML = '';

        analytics.loadPlausible(config);
        expect(document.head.querySelectorAll('script')).toHaveLength(1);
    });
});
