import { router } from '@inertiajs/vue3';
import type { Updater } from '@tanstack/vue-table';
import { useDebounceFn } from '@vueuse/core';
import { computed, reactive, ref } from 'vue';
import { valueUpdater } from '@/components/ui/table/utils';

export interface DataTableFilters {
    search?: string;
    sort?: string;
    direction?: 'asc' | 'desc';
    per_page?: number;
    [key: string]: string | number | undefined;
}

export function useDataTable(
    routeUrl: () => string,
    initialFilters: DataTableFilters = {},
) {
    const filters = reactive<DataTableFilters>({ ...initialFilters });
    const rowSelection = ref<Record<string, boolean>>({});

    function navigate(overrides: DataTableFilters = {}) {
        const params: Record<string, string | number> = {};

        for (const [key, value] of Object.entries({
            ...filters,
            ...overrides,
        })) {
            if (value !== undefined && value !== '' && value !== null) {
                params[key] = value;
            }
        }

        router.get(routeUrl(), params, {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        });
    }

    const debouncedSearch = useDebounceFn(
        () => navigate({ page: undefined }),
        300,
    );

    function setSearch(value: string) {
        filters.search = value || undefined;
        debouncedSearch();
    }

    function toggleSort(column: string) {
        if (filters.sort === column) {
            filters.direction = filters.direction === 'asc' ? 'desc' : 'asc';
        } else {
            filters.sort = column;
            filters.direction = 'asc';
        }

        navigate({ page: undefined });
    }

    function setFilter(key: string, value: string | undefined) {
        filters[key] = value;
        navigate({ page: undefined });
    }

    function setPage(page: number) {
        navigate({ page });
    }

    function setPerPage(perPage: number) {
        filters.per_page = perPage;
        navigate({ page: undefined });
    }

    function updateRowSelection(
        updaterOrValue: Updater<Record<string, boolean>>,
    ) {
        valueUpdater(updaterOrValue, rowSelection);
    }

    function clearSelection() {
        rowSelection.value = {};
    }

    const selectedCount = computed(
        () => Object.keys(rowSelection.value).length,
    );
    const selectedIds = computed(() =>
        Object.keys(rowSelection.value).map(Number),
    );

    return {
        filters,
        rowSelection,
        selectedCount,
        selectedIds,
        setSearch,
        toggleSort,
        setFilter,
        setPage,
        setPerPage,
        updateRowSelection,
        clearSelection,
        navigate,
    };
}
