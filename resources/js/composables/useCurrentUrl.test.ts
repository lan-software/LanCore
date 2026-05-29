import { describe, expect, it, vi } from 'vitest';

// `useCurrentUrl` reads `usePage()` at module load and derives the path from
// `page.url`, so the mocked page url is fixed for the whole file.
vi.mock('@inertiajs/vue3', () => ({
    usePage: () => ({ url: '/events/42', props: {} }),
}));

import { useCurrentUrl } from './useCurrentUrl';

describe('useCurrentUrl', () => {
    it('derives the current pathname from the page url', () => {
        const { currentUrl } = useCurrentUrl();
        expect(currentUrl.value).toBe('/events/42');
    });

    it('matches an exact relative url against the current path', () => {
        const { isCurrentUrl } = useCurrentUrl();
        expect(isCurrentUrl('/events/42')).toBe(true);
        expect(isCurrentUrl('/events/7')).toBe(false);
    });

    it('honours an explicit currentUrl override', () => {
        const { isCurrentUrl } = useCurrentUrl();
        expect(isCurrentUrl('/foo', '/foo')).toBe(true);
        expect(isCurrentUrl('/foo', '/bar')).toBe(false);
    });

    it('isCurrentOrParentUrl does a startsWith (prefix) match', () => {
        const { isCurrentOrParentUrl } = useCurrentUrl();
        expect(isCurrentOrParentUrl('/events', '/events/42')).toBe(true);
        expect(isCurrentOrParentUrl('/users', '/events/42')).toBe(false);
    });

    it('compares the pathname of absolute http urls', () => {
        const { isCurrentUrl } = useCurrentUrl();
        expect(isCurrentUrl('http://localhost/events/42', '/events/42')).toBe(
            true,
        );
        expect(isCurrentUrl('https://example.test/other', '/events/42')).toBe(
            false,
        );
    });

    it('returns false for a malformed absolute url', () => {
        const { isCurrentUrl } = useCurrentUrl();
        expect(isCurrentUrl('http://', '/events/42')).toBe(false);
    });

    it('accepts an Inertia href object via toUrl', () => {
        const { isCurrentUrl } = useCurrentUrl();
        expect(isCurrentUrl({ url: '/events/42' } as never)).toBe(true);
    });

    it('whenCurrentUrl returns the truthy/falsey branch', () => {
        const { whenCurrentUrl } = useCurrentUrl();
        expect(whenCurrentUrl('/events/42', 'YES', 'NO')).toBe('YES');
        expect(whenCurrentUrl('/nope', 'YES', 'NO')).toBe('NO');
        expect(whenCurrentUrl('/nope', 'YES')).toBeNull();
    });
});
