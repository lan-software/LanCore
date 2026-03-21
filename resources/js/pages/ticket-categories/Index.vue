<script setup lang="ts">
import { FlexRender, getCoreRowModel, useVueTable, type SortingState } from '@tanstack/vue-table'
import { router, Head, Link } from '@inertiajs/vue3'
import TicketCategoryController from '@/actions/App/Domain/Ticketing/Http/Controllers/TicketCategoryController'
import { ChevronLeft, ChevronRight, Plus, Search } from 'lucide-vue-next'
import { computed, ref, watch } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Table, TableBody, TableCell, TableEmpty, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { useDataTable, type DataTableFilters } from '@/composables/useDataTable'
import { index as ticketCategoriesIndex } from '@/routes/ticket-categories'
import type { BreadcrumbItem } from '@/types'
import type { TicketCategory } from '@/types/domain'
import { columns } from './columns'

interface PaginatedTicketCategories {
    data: TicketCategory[]
    current_page: number
    last_page: number
    per_page: number
    total: number
    from: number | null
    to: number | null
}

const props = defineProps<{
    ticketCategories: PaginatedTicketCategories
    filters: DataTableFilters
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: ticketCategoriesIndex().url },
    { title: 'Ticket Categories', href: ticketCategoriesIndex().url },
]

const { filters, setSearch, toggleSort, setFilter, setPage, setPerPage } =
    useDataTable(() => ticketCategoriesIndex().url, props.filters)

const searchValue = ref(props.filters.search ?? '')

watch(searchValue, (val) => setSearch(val))

const sorting = computed<SortingState>(() =>
    props.filters.sort ? [{ id: props.filters.sort, desc: props.filters.direction === 'desc' }] : [],
)

const table = useVueTable({
    get data() {
        return props.ticketCategories.data
    },
    columns,
    getCoreRowModel: getCoreRowModel(),
    manualSorting: true,
    manualFiltering: true,
    manualPagination: true,
    rowCount: props.ticketCategories.total,
    getRowId: (row) => String(row.id),
    state: {
        get sorting() {
            return sorting.value
        },
    },
    onSortingChange: (updater) => {
        const newSorting = typeof updater === 'function' ? updater(sorting.value) : updater
        if (newSorting.length > 0) {
            toggleSort(newSorting[0].id)
        } else {
            setFilter('sort', undefined)
            setFilter('direction', undefined)
        }
    },
})

const perPageOptions = [10, 20, 50, 100]
</script>

<template>
    <Head title="Ticket Categories" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <!-- Toolbar -->
            <div class="flex flex-wrap items-center gap-2">
                <!-- Search -->
                <div class="relative flex-1 min-w-48">
                    <Search class="absolute left-2.5 top-2.5 size-4 text-muted-foreground" />
                    <Input
                        v-model="searchValue"
                        placeholder="Search ticket categories…"
                        class="pl-8"
                    />
                </div>

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
                    <Link :href="TicketCategoryController.create().url">
                        <Plus class="size-4" />
                        Create Category
                    </Link>
                </Button>
            </div>

            <!-- Table -->
            <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
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
                                @click="router.visit(TicketCategoryController.edit(row.original.id).url)"
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
                        <TableEmpty
                            v-else
                            :colspan="columns.length"
                        >
                            No ticket categories found.
                        </TableEmpty>
                    </TableBody>
                </Table>

                <!-- Pagination -->
                <div class="flex items-center justify-between border-t border-sidebar-border/70 px-4 py-3 dark:border-sidebar-border">
                    <span class="text-xs text-muted-foreground">
                        <template v-if="ticketCategories.from && ticketCategories.to">
                            Showing {{ ticketCategories.from }}–{{ ticketCategories.to }} of {{ ticketCategories.total }} ticket categories
                        </template>
                        <template v-else>
                            {{ ticketCategories.total }} ticket categories
                        </template>
                    </span>
                    <div class="flex items-center gap-1">
                        <Button
                            variant="outline"
                            size="sm"
                            class="size-8 p-0"
                            :disabled="ticketCategories.current_page <= 1"
                            @click="setPage(ticketCategories.current_page - 1)"
                        >
                            <ChevronLeft class="size-4" />
                        </Button>
                        <span class="px-2 text-xs text-muted-foreground">
                            {{ ticketCategories.current_page }} / {{ ticketCategories.last_page }}
                        </span>
                        <Button
                            variant="outline"
                            size="sm"
                            class="size-8 p-0"
                            :disabled="ticketCategories.current_page >= ticketCategories.last_page"
                            @click="setPage(ticketCategories.current_page + 1)"
                        >
                            <ChevronRight class="size-4" />
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
