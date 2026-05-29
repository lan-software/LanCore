import { router } from '@inertiajs/vue3';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { useDataTable } from './useDataTable';

vi.mock('@inertiajs/vue3', () => ({
    router: { get: vi.fn() },
}));

const routerGet = router.get as unknown as ReturnType<typeof vi.fn>;
const url = () => '/admin/users';

beforeEach(() => {
    routerGet.mockClear();
});

describe('useDataTable navigate', () => {
    it('drops undefined/empty/null params before navigating', () => {
        const t = useDataTable(url, {
            search: '',
            sort: 'name',
            per_page: 25,
        });
        t.navigate();

        expect(routerGet).toHaveBeenCalledTimes(1);
        const [calledUrl, params, opts] = routerGet.mock.calls[0];
        expect(calledUrl).toBe('/admin/users');
        expect(params).toEqual({ sort: 'name', per_page: 25 });
        expect(opts).toMatchObject({
            preserveScroll: true,
            preserveState: true,
            replace: true,
        });
    });

    it('merges overrides over the current filters', () => {
        const t = useDataTable(url, { sort: 'name' });
        t.navigate({ sort: 'email', page: 2 });
        expect(routerGet.mock.calls[0][1]).toEqual({ sort: 'email', page: 2 });
    });
});

describe('useDataTable sorting & filters', () => {
    it('toggleSort sets asc then flips to desc on the same column', () => {
        const t = useDataTable(url);

        t.toggleSort('name');
        expect(t.filters.sort).toBe('name');
        expect(t.filters.direction).toBe('asc');

        t.toggleSort('name');
        expect(t.filters.direction).toBe('desc');

        t.toggleSort('email');
        expect(t.filters.sort).toBe('email');
        expect(t.filters.direction).toBe('asc');
        expect(routerGet).toHaveBeenCalledTimes(3);
    });

    it('setFilter, setPage and setPerPage navigate with the new state', () => {
        const t = useDataTable(url);

        t.setFilter('status', 'active');
        expect(t.filters.status).toBe('active');

        t.setPage(3);
        expect(routerGet.mock.calls.at(-1)?.[1]).toMatchObject({ page: 3 });

        t.setPerPage(50);
        expect(t.filters.per_page).toBe(50);
    });
});

describe('useDataTable search (debounced)', () => {
    beforeEach(() => vi.useFakeTimers());
    afterEach(() => vi.useRealTimers());

    it('debounces the search navigation by 300ms', () => {
        const t = useDataTable(url);

        t.setSearch('neo');
        expect(t.filters.search).toBe('neo');
        expect(routerGet).not.toHaveBeenCalled();

        vi.advanceTimersByTime(300);
        expect(routerGet).toHaveBeenCalledTimes(1);
        expect(routerGet.mock.calls[0][1]).toMatchObject({ search: 'neo' });
    });

    it('clears the search filter when given an empty string', () => {
        const t = useDataTable(url);
        t.setSearch('');
        expect(t.filters.search).toBeUndefined();
    });
});

describe('useDataTable row selection', () => {
    it('updates selection via a value or an updater function', () => {
        const t = useDataTable(url);

        t.updateRowSelection({ '1': true, '2': true });
        expect(t.selectedCount.value).toBe(2);
        expect(t.selectedIds.value).toEqual([1, 2]);

        t.updateRowSelection((prev) => ({ ...prev, '3': true }));
        expect(t.selectedCount.value).toBe(3);

        t.clearSelection();
        expect(t.selectedCount.value).toBe(0);
        expect(t.selectedIds.value).toEqual([]);
    });
});
