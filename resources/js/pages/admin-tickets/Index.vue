<script setup lang="ts">
import { router, Head } from '@inertiajs/vue3';
import { FlexRender, getCoreRowModel, useVueTable } from '@tanstack/vue-table';
import type { SortingState } from '@tanstack/vue-table';
import { ChevronLeft, ChevronRight, Search } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { show as adminTicketShow } from '@/actions/App/Domain/Ticketing/Http/Controllers/AdminTicketController';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
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
import { index as adminTicketsIndex } from '@/routes/admin-tickets';
import type { BreadcrumbItem } from '@/types';
import type { Ticket } from '@/types/domain';
import { columns } from './columns';

interface PaginatedTickets {
    data: Ticket[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
}

const props = defineProps<{
    tickets: PaginatedTickets;
    filters: DataTableFilters;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: adminTicketsIndex().url },
    { title: 'Tickets', href: adminTicketsIndex().url },
];

const { filters, setSearch, toggleSort, setFilter, setPage, setPerPage } =
    useDataTable(() => adminTicketsIndex().url, props.filters);

const searchValue = ref(props.filters.search ?? '');

watch(searchValue, (val) => setSearch(val));

const sorting = computed<SortingState>(() =>
    props.filters.sort
        ? [{ id: props.filters.sort, desc: props.filters.direction === 'desc' }]
        : [],
);

const table = useVueTable({
    get data() {
        return props.tickets.data;
    },
    columns,
    getCoreRowModel: getCoreRowModel(),
    manualSorting: true,
    manualFiltering: true,
    manualPagination: true,
    rowCount: props.tickets.total,
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
        }
    },
});

const perPageOptions = [10, 20, 50, 100];
</script>

<template>
    <Head title="Tickets Management" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex flex-wrap items-center gap-2">
                <div class="relative min-w-48 flex-1">
                    <Search
                        class="absolute top-2.5 left-2.5 size-4 text-muted-foreground"
                    />
                    <Input
                        v-model="searchValue"
                        placeholder="Search tickets…"
                        class="pl-8"
                    />
                </div>

                <Select
                    :model-value="(filters.status as string) ?? ''"
                    @update:model-value="
                        (val) => setFilter('status', val || undefined)
                    "
                >
                    <SelectTrigger class="w-36">
                        <SelectValue placeholder="All Statuses" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="">All Statuses</SelectItem>
                        <SelectItem value="active">Active</SelectItem>
                        <SelectItem value="checked_in">Checked In</SelectItem>
                        <SelectItem value="cancelled">Cancelled</SelectItem>
                    </SelectContent>
                </Select>

                <Select
                    :model-value="String(filters.per_page ?? 20)"
                    @update:model-value="(val) => setPerPage(Number(val))"
                >
                    <SelectTrigger class="w-24">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="n in perPageOptions"
                            :key="n"
                            :value="String(n)"
                        >
                            {{ n }} / page
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <div
                class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
            >
                <Table>
                    <TableHeader>
                        <TableRow
                            v-for="headerGroup in table.getHeaderGroups()"
                            :key="headerGroup.id"
                        >
                            <TableHead
                                v-for="header in headerGroup.headers"
                                :key="header.id"
                                class="px-2"
                            >
                                <FlexRender
                                    v-if="!header.isPlaceholder"
                                    :render="header.column.columnDef.header"
                                    :props="header.getContext()"
                                />
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <template v-if="table.getRowModel().rows.length">
                            <TableRow
                                v-for="row in table.getRowModel().rows"
                                :key="row.id"
                                class="cursor-pointer"
                                @click="
                                    router.visit(
                                        adminTicketShow(row.original.id).url,
                                    )
                                "
                            >
                                <TableCell
                                    v-for="cell in row.getVisibleCells()"
                                    :key="cell.id"
                                    class="px-4 py-3"
                                >
                                    <FlexRender
                                        :render="cell.column.columnDef.cell"
                                        :props="cell.getContext()"
                                    />
                                </TableCell>
                            </TableRow>
                        </template>
                        <TableEmpty v-else :colspan="columns.length">
                            No tickets found.
                        </TableEmpty>
                    </TableBody>
                </Table>

                <div
                    class="flex items-center justify-between border-t border-sidebar-border/70 px-4 py-3 dark:border-sidebar-border"
                >
                    <span class="text-xs text-muted-foreground">
                        <template v-if="tickets.from && tickets.to">
                            Showing {{ tickets.from }}–{{ tickets.to }} of
                            {{ tickets.total }} tickets
                        </template>
                        <template v-else>{{ tickets.total }} tickets</template>
                    </span>
                    <div class="flex items-center gap-1">
                        <Button
                            variant="outline"
                            size="sm"
                            class="size-8 p-0"
                            :disabled="tickets.current_page <= 1"
                            @click="setPage(tickets.current_page - 1)"
                        >
                            <ChevronLeft class="size-4" />
                        </Button>
                        <span class="px-2 text-xs text-muted-foreground"
                            >{{ tickets.current_page }} /
                            {{ tickets.last_page }}</span
                        >
                        <Button
                            variant="outline"
                            size="sm"
                            class="size-8 p-0"
                            :disabled="
                                tickets.current_page >= tickets.last_page
                            "
                            @click="setPage(tickets.current_page + 1)"
                        >
                            <ChevronRight class="size-4" />
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
