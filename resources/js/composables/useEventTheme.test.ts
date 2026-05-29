import { beforeEach, describe, expect, it, vi } from 'vitest';

const pageProps: { activeTheme?: unknown } = {};

vi.mock('@inertiajs/vue3', () => ({
    usePage: () => ({ props: pageProps }),
}));

import { useEventTheme } from './useEventTheme';

beforeEach(() => {
    delete pageProps.activeTheme;
});

describe('useEventTheme', () => {
    it('exposes the active theme and its light/dark configs', () => {
        pageProps.activeTheme = {
            lightConfig: { '--primary': '#fff' },
            darkConfig: { '--primary': '#000' },
        };

        const { activeTheme, lightConfig, darkConfig } = useEventTheme();
        expect(activeTheme.value).toMatchObject({
            lightConfig: { '--primary': '#fff' },
        });
        expect(lightConfig.value).toEqual({ '--primary': '#fff' });
        expect(darkConfig.value).toEqual({ '--primary': '#000' });
    });

    it('returns null theme and empty configs when no theme is shared', () => {
        const { activeTheme, lightConfig, darkConfig } = useEventTheme();
        expect(activeTheme.value).toBeNull();
        expect(lightConfig.value).toEqual({});
        expect(darkConfig.value).toEqual({});
    });

    it('defends against a theme that omits the config maps', () => {
        pageProps.activeTheme = {};
        const { lightConfig, darkConfig } = useEventTheme();
        expect(lightConfig.value).toEqual({});
        expect(darkConfig.value).toEqual({});
    });
});
