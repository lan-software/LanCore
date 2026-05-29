import { beforeEach, describe, expect, it, vi } from 'vitest';

const pageProps: { presence?: unknown } = {};

vi.mock('@inertiajs/vue3', () => ({
    usePage: () => ({ props: pageProps }),
}));

import { useMyPresence, useUsersPresence } from './usePresence';

beforeEach(() => {
    delete pageProps.presence;
});

describe('useMyPresence', () => {
    it('reads the status from the shared presence prop', () => {
        pageProps.presence = { status: 'active' };
        expect(useMyPresence().value).toBe('active');
    });

    it('falls back to offline when the prop is missing', () => {
        expect(useMyPresence().value).toBe('offline');
    });
});

describe('useUsersPresence', () => {
    it('builds a status map for the requested user ids', () => {
        pageProps.presence = { 1: 'active', 2: 'idle' };
        const map = useUsersPresence([1, 2]).value;
        expect(map).toEqual({ 1: 'active', 2: 'idle' });
    });

    it('defaults unknown users to offline', () => {
        pageProps.presence = { 1: 'active' };
        const map = useUsersPresence([1, 99]).value;
        expect(map).toEqual({ 1: 'active', 99: 'offline' });
    });

    it('returns all-offline when the prop is missing', () => {
        const map = useUsersPresence([7, 8]).value;
        expect(map).toEqual({ 7: 'offline', 8: 'offline' });
    });
});
