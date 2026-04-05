<script setup lang="ts">
import { router, Head, Link } from '@inertiajs/vue3';
import { FlexRender, getCoreRowModel, useVueTable } from '@tanstack/vue-table';
import type { SortingState } from '@tanstack/vue-table';
import { ChevronLeft, ChevronRight, Plus, Search } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { edit } from '@/actions/App/Domain/Program/Http/Controllers/ProgramController';
import { create as programCreate } from '@/actions/App/Domain/Program/Http/Controllers/ProgramController';
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
import { index as programsRoute } from '@/routes/programs';
import type { BreadcrumbItem } from '@/types';
import type { Program } from '@/types/domain';
import { columns } from './columns';

interface PaginatedPrograms {
    data: Program[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
}

const props = defineProps<{
    programs: PaginatedPrograms;
    filters: DataTableFilters;
    events: { id: number; name: string }[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: programsRoute().url },
    { title: 'Programs', href: programsRoute().url },
];

const { filters, setSearch, toggleSort, setFilter, setPage, setPerPage } =
    useDataTable(() => programsRoute().url, props.filters);

const searchValue = ref(props.filters.search ?? '');

watch(searchValue, (val) => setSearch(val));

const sorting = computed<SortingState>(() =>
    props.filters.sort
        ? [{ id: props.filters.sort, desc: props.filters.direction === 'desc' }]
        : [],
);

const table = useVueTable({
    get data() {
        return props.programs.data;
    },
    columns,
    getCoreRowModel: getCoreRowModel(),
    manualSorting: true,
    manualFiltering: true,
    manualPagination: true,
    rowCount: props.programs.total,
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

const perPageOptions = [10, 20, 50, 100];
</script>

<template>
    <Head title="Programs" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <!-- Toolbar -->
            <div class="flex flex-wrap items-center gap-2">
                <!-- Search -->
                <div class="relative min-w-48 flex-1">
                    <Search
                        class="absolute top-2.5 left-2.5 size-4 text-muted-foreground"
                    />
                    <Input
                        v-model="searchValue"
                        placeholder="Search programs…"
                        class="pl-8"
                    />
                </div>

                <!-- Event filter -->
                <Select
                    :model-value="(filters.event_id as string) ?? 'all'"
                    @update:model-value="
                        (val) =>
                            setFilter(
                                'event_id',
                                val === 'all' ? undefined : val,
                            )
                    "
                >
                    <SelectTrigger class="w-44">
                        <SelectValue placeholder="All events" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All events</SelectItem>
                        <SelectItem
                            v-for="event in events"
                            :key="event.id"
                            :value="String(event.id)"
                        >
                            {{ event.name }}
                        </SelectItem>
                    </SelectContent>
                </Select>

                <!-- Per-page selector -->
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

                <!-- Create button -->
                <Button as-child>
                    <Link :href="programCreate().url">
                        <Plus class="size-4" />
                        Create Program
                    </Link>
                </Button>
            </div>

            <!-- Table -->
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
                                @click="router.visit(edit(row.original.id).url)"
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
                            No programs found.
                        </TableEmpty>
                    </TableBody>
                </Table>

                <!-- Pagination -->
                <div
                    class="flex items-center justify-between border-t border-sidebar-border/70 px-4 py-3 dark:border-sidebar-border"
                >
                    <span class="text-xs text-muted-foreground">
                        <template v-if="programs.from && programs.to">
                            Showing {{ programs.from }}–{{ programs.to }} of
                            {{ programs.total }} programs
                        </template>
                        <template v-else>
                            {{ programs.total }} programs
                        </template>
                    </span>
                    <div class="flex items-center gap-1">
                        <Button
                            variant="outline"
                            size="sm"
                            class="size-8 p-0"
                            :disabled="programs.current_page <= 1"
                            @click="setPage(programs.current_page - 1)"
                        >
                            <ChevronLeft class="size-4" />
                        </Button>
                        <span class="px-2 text-xs text-muted-foreground">
                            {{ programs.current_page }} /
                            {{ programs.last_page }}
                        </span>
                        <Button
                            variant="outline"
                            size="sm"
                            class="size-8 p-0"
                            :disabled="
                                programs.current_page >= programs.last_page
                            "
                            @click="setPage(programs.current_page + 1)"
                        >
                            <ChevronRight class="size-4" />
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
