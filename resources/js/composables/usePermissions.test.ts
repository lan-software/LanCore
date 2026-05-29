import { beforeEach, describe, expect, it, vi } from 'vitest';

const pageProps: { permissions?: string[] } = {};

vi.mock('@inertiajs/vue3', () => ({
    usePage: () => ({ props: pageProps }),
}));

import { usePermissions } from './usePermissions';

beforeEach(() => {
    delete pageProps.permissions;
});

describe('usePermissions', () => {
    it('exposes the shared permissions list reactively', () => {
        pageProps.permissions = ['users.view', 'users.edit'];
        const { permissions } = usePermissions();
        expect(permissions.value).toEqual(['users.view', 'users.edit']);
    });

    it('defaults to an empty list when the prop is absent', () => {
        const { permissions, can } = usePermissions();
        expect(permissions.value).toEqual([]);
        expect(can('users.view' as never)).toBe(false);
    });

    it('can() checks a single permission', () => {
        pageProps.permissions = ['users.view'];
        const { can } = usePermissions();
        expect(can('users.view' as never)).toBe(true);
        expect(can('users.delete' as never)).toBe(false);
    });

    it('canAny() is true when at least one permission matches', () => {
        pageProps.permissions = ['users.view'];
        const { canAny } = usePermissions();
        expect(canAny('users.delete' as never, 'users.view' as never)).toBe(
            true,
        );
        expect(canAny('users.delete' as never, 'users.edit' as never)).toBe(
            false,
        );
        expect(canAny()).toBe(false);
    });
});
