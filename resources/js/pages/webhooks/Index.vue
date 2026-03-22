<script setup lang="ts">
import { FlexRender, getCoreRowModel, useVueTable, type SortingState } from '@tanstack/vue-table'
import { router, Head, Link } from '@inertiajs/vue3'
import { edit, show } from '@/actions/App/Domain/Webhook/Http/Controllers/WebhookController'
import { create as webhookCreate } from '@/actions/App/Domain/Webhook/Http/Controllers/WebhookController'
import { ChevronLeft, ChevronRight, Plus, Search } from 'lucide-vue-next'
import { computed, ref, watch } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Table, TableBody, TableCell, TableEmpty, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { useDataTable, type DataTableFilters } from '@/composables/useDataTable'
import { index as webhooksRoute } from '@/routes/webhooks'
import type { BreadcrumbItem } from '@/types'
import type { Webhook } from '@/types/domain'
import { columns } from './columns'

interface PaginatedWebhooks {
    data: Webhook[]
    current_page: number
    last_page: number
    per_page: number
    total: number
    from: number | null
    to: number | null
}

const props = defineProps<{
    webhooks: PaginatedWebhooks
    filters: DataTableFilters
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: webhooksRoute().url },
    { title: 'Webhooks', href: webhooksRoute().url },
]

const { filters, setSearch, toggleSort, setFilter, setPage } =
    useDataTable(() => webhooksRoute().url, props.filters)

const searchValue = ref(props.filters.search ?? '')

watch(searchValue, (val) => setSearch(val))

const sorting = computed<SortingState>(() =>
    props.filters.sort ? [{ id: props.filters.sort, desc: props.filters.direction === 'desc' }] : [],
)

const table = useVueTable({
    get data() {
        return props.webhooks.data
    },
    columns,
    getCoreRowModel: getCoreRowModel(),
    manualSorting: true,
    manualFiltering: true,
    manualPagination: true,
    rowCount: props.webhooks.total,
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
</script>

<template>
    <Head title="Webhooks" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <!-- Toolbar -->
            <div class="flex flex-wrap items-center gap-2">
                <div class="relative flex-1 min-w-48">
                    <Search class="absolute left-2.5 top-2.5 size-4 text-muted-foreground" />
                    <Input
                        v-model="searchValue"
                        placeholder="Search webhooks…"
                        class="pl-8"
                    />
                </div>
                <Link :href="webhookCreate().url" as-button>
                    <Button>
                        <Plus class="mr-2 size-4" />
                        Create Webhook
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
                        @click="router.visit(show({ webhook: row.original.id }).url)"
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
                    <TableEmpty v-if="table.getRowModel().rows.length === 0" :columns-count="table.getAllColumns().length" />
                </TableBody>
            </Table>

            <!-- Pagination -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="text-sm text-muted-foreground">
                        {{ props.webhooks.from }}-{{ props.webhooks.to }} of {{ props.webhooks.total }}
                    </span>
                </div>
                <div class="flex items-center gap-2">
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="props.webhooks.current_page === 1"
                        @click="setPage(props.webhooks.current_page - 1)"
                    >
                        <ChevronLeft class="size-4" />
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="props.webhooks.current_page === props.webhooks.last_page"
                        @click="setPage(props.webhooks.current_page + 1)"
                    >
                        <ChevronRight class="size-4" />
                    </Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
