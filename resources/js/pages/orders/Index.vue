<script setup lang="ts">
import { FlexRender, getCoreRowModel, useVueTable, type SortingState } from '@tanstack/vue-table'
import { router, Head } from '@inertiajs/vue3'
import OrderController from '@/actions/App/Domain/Shop/Http/Controllers/OrderController'
import { ChevronLeft, ChevronRight, Search } from 'lucide-vue-next'
import { computed, ref, watch } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Table, TableBody, TableCell, TableEmpty, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { useDataTable, type DataTableFilters } from '@/composables/useDataTable'
import { index as ordersIndex } from '@/routes/orders'
import type { BreadcrumbItem } from '@/types'
import type { Order } from '@/types/domain'
import { columns } from './columns'

interface PaginatedOrders {
    data: Order[]
    current_page: number
    last_page: number
    per_page: number
    total: number
    from: number | null
    to: number | null
}

const props = defineProps<{
    orders: PaginatedOrders
    filters: DataTableFilters
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: ordersIndex().url },
    { title: 'Orders', href: ordersIndex().url },
]

const { filters, setSearch, toggleSort, setFilter, setPage, setPerPage } =
    useDataTable(() => ordersIndex().url, props.filters)

const searchValue = ref(props.filters.search ?? '')

watch(searchValue, (val) => setSearch(val))

const sorting = computed<SortingState>(() =>
    props.filters.sort ? [{ id: props.filters.sort, desc: props.filters.direction === 'desc' }] : [],
)

const table = useVueTable({
    get data() {
        return props.orders.data
    },
    columns,
    getCoreRowModel: getCoreRowModel(),
    manualSorting: true,
    manualFiltering: true,
    manualPagination: true,
    rowCount: props.orders.total,
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
        }
    },
})

const perPageOptions = [10, 20, 50, 100]
</script>

<template>
    <Head title="Orders" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex flex-wrap items-center gap-2">
                <div class="relative flex-1 min-w-48">
                    <Search class="absolute left-2.5 top-2.5 size-4 text-muted-foreground" />
                    <Input v-model="searchValue" placeholder="Search orders…" class="pl-8" />
                </div>

                <Select
                    :model-value="(filters.status as string) ?? ''"
                    @update:model-value="(val) => setFilter('status', val || undefined)"
                >
                    <SelectTrigger class="w-36">
                        <SelectValue placeholder="All Statuses" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="">All Statuses</SelectItem>
                        <SelectItem value="pending">Pending</SelectItem>
                        <SelectItem value="completed">Completed</SelectItem>
                        <SelectItem value="failed">Failed</SelectItem>
                        <SelectItem value="refunded">Refunded</SelectItem>
                    </SelectContent>
                </Select>

                <Select
                    :model-value="(filters.payment_method as string) ?? ''"
                    @update:model-value="(val) => setFilter('payment_method', val || undefined)"
                >
                    <SelectTrigger class="w-36">
                        <SelectValue placeholder="All Payments" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="">All Payments</SelectItem>
                        <SelectItem value="stripe">Credit Card</SelectItem>
                        <SelectItem value="on_site">On Site</SelectItem>
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
                        <SelectItem v-for="n in perPageOptions" :key="n" :value="String(n)">
                            {{ n }} / page
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                <Table>
                    <TableHeader>
                        <TableRow v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
                            <TableHead v-for="header in headerGroup.headers" :key="header.id" class="px-2">
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
                                @click="router.visit(OrderController.show(row.original.id).url)"
                            >
                                <TableCell v-for="cell in row.getVisibleCells()" :key="cell.id" class="px-4 py-3">
                                    <FlexRender :render="cell.column.columnDef.cell" :props="cell.getContext()" />
                                </TableCell>
                            </TableRow>
                        </template>
                        <TableEmpty v-else :colspan="columns.length">
                            No orders found.
                        </TableEmpty>
                    </TableBody>
                </Table>

                <div class="flex items-center justify-between border-t border-sidebar-border/70 px-4 py-3 dark:border-sidebar-border">
                    <span class="text-xs text-muted-foreground">
                        <template v-if="orders.from && orders.to">
                            Showing {{ orders.from }}–{{ orders.to }} of {{ orders.total }} orders
                        </template>
                        <template v-else>{{ orders.total }} orders</template>
                    </span>
                    <div class="flex items-center gap-1">
                        <Button variant="outline" size="sm" class="size-8 p-0" :disabled="orders.current_page <= 1" @click="setPage(orders.current_page - 1)">
                            <ChevronLeft class="size-4" />
                        </Button>
                        <span class="px-2 text-xs text-muted-foreground">{{ orders.current_page }} / {{ orders.last_page }}</span>
                        <Button variant="outline" size="sm" class="size-8 p-0" :disabled="orders.current_page >= orders.last_page" @click="setPage(orders.current_page + 1)">
                            <ChevronRight class="size-4" />
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
