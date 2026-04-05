<script setup lang="ts">
import { router, Head, Link } from '@inertiajs/vue3';
import { FlexRender, getCoreRowModel, useVueTable } from '@tanstack/vue-table';
import type { SortingState } from '@tanstack/vue-table';
import { ChevronLeft, ChevronRight, Plus, Search } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { edit } from '@/actions/App/Domain/Announcement/Http/Controllers/AnnouncementController';
import { create as announcementCreate } from '@/actions/App/Domain/Announcement/Http/Controllers/AnnouncementController';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableEmpty,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useDataTable } from '@/composables/useDataTable';
import type { DataTableFilters } from '@/composables/useDataTable';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as announcementsRoute } from '@/routes/announcements';
import type { BreadcrumbItem } from '@/types';
import type { Announcement } from '@/types/domain';
import { columns } from './columns';

interface PaginatedAnnouncements {
    data: Announcement[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
}

const props = defineProps<{
    announcements: PaginatedAnnouncements;
    filters: DataTableFilters;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: announcementsRoute().url },
    { title: 'Announcements', href: announcementsRoute().url },
];

const { setSearch, toggleSort, setFilter, setPage } = useDataTable(
    () => announcementsRoute().url,
    props.filters,
);

const searchValue = ref(props.filters.search ?? '');

watch(searchValue, (val) => setSearch(val));

const sorting = computed<SortingState>(() =>
    props.filters.sort
        ? [{ id: props.filters.sort, desc: props.filters.direction === 'desc' }]
        : [],
);

const table = useVueTable({
    get data() {
        return props.announcements.data;
    },
    columns,
    getCoreRowModel: getCoreRowModel(),
    manualSorting: true,
    manualFiltering: true,
    manualPagination: true,
    rowCount: props.announcements.total,
    getRowId: (row) => String(row.id),
    state: {
        get sorting() {
            return sorting.value;
        },
    },
    onSortingChange: (updater) => {
        const newSorting =
            typeof updater === 'function' ? updater(sorting.value) : updater;

        if (newSorting.length > 0) {
            toggleSort(newSorting[0].id);
        } else {
            setFilter('sort', undefined);
            setFilter('direction', undefined);
        }
    },
});
</script>

<template>
    <Head title="Announcements" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <!-- Toolbar -->
            <div class="flex flex-wrap items-center gap-2">
                <div class="relative min-w-48 flex-1">
                    <Search
                        class="absolute top-2.5 left-2.5 size-4 text-muted-foreground"
                    />
                    <Input
                        v-model="searchValue"
                        placeholder="Search announcements…"
                        class="pl-8"
                    />
                </div>
                <Link :href="announcementCreate().url" as-button>
                    <Button>
                        <Plus class="mr-2 size-4" />
                        Create Announcement
                    </Button>
                </Link>
            </div>

            <!-- Table -->
            <Table class="border">
                <TableHeader>
                    <TableRow>
                        <TableHead
                            v-for="header in table.getFlatHeaders()"
                            :key="header.id"
                            class="border-r last:border-r-0"
                        >
                            <FlexRender
                                :render="header.column.columnDef.header"
                                :props="header.getContext()"
                            />
                        </TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow
                        v-for="row in table.getRowModel().rows"
                        :key="row.id"
                        class="cursor-pointer hover:bg-muted/50"
                        @click="
                            router.visit(
                                edit({ announcement: row.original.id }).url,
                            )
                        "
                    >
                        <TableCell
                            v-for="cell in row.getVisibleCells()"
                            :key="cell.id"
                            class="border-r last:border-r-0"
                        >
                            <FlexRender
                                :render="cell.column.columnDef.cell"
                                :props="cell.getContext()"
                            />
                        </TableCell>
                    </TableRow>
                    <TableEmpty
                        v-if="table.getRowModel().rows.length === 0"
                        :columns-count="table.getAllColumns().length"
                    />
                </TableBody>
            </Table>

            <!-- Pagination -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="text-sm text-muted-foreground">
                        {{ props.announcements.from }}-{{
                            props.announcements.to
                        }}
                        of {{ props.announcements.total }}
                    </span>
                </div>
                <div class="flex items-center gap-2">
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="props.announcements.current_page === 1"
                        @click="setPage(props.announcements.current_page - 1)"
                    >
                        <ChevronLeft class="size-4" />
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="
                            props.announcements.current_page ===
                            props.announcements.last_page
                        "
                        @click="setPage(props.announcements.current_page + 1)"
                    >
                        <ChevronRight class="size-4" />
                    </Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
