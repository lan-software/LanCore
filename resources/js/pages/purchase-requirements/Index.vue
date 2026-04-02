<script setup lang="ts">
import { router, Head, Link } from '@inertiajs/vue3'
import { FlexRender, getCoreRowModel, useVueTable  } from '@tanstack/vue-table'
import type {ColumnDef} from '@tanstack/vue-table';
import { ChevronLeft, ChevronRight, Plus } from 'lucide-vue-next'
import { h } from 'vue'
import PurchaseRequirementController from '@/actions/App/Domain/Shop/Http/Controllers/PurchaseRequirementController'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Table, TableBody, TableCell, TableEmpty, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import AppLayout from '@/layouts/AppLayout.vue'
import { index as requirementsIndex } from '@/routes/purchase-requirements'
import type { BreadcrumbItem } from '@/types'

type PurchaseRequirementRow = {
    id: number
    name: string
    is_active: boolean
    ticket_types_count: number
    addons_count: number
    created_at: string
}

interface Paginated {
    data: PurchaseRequirementRow[]
    current_page: number
    last_page: number
    per_page: number
    total: number
    from: number | null
    to: number | null
}

const props = defineProps<{
    requirements: Paginated
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: requirementsIndex().url },
    { title: 'Purchase Requirements', href: requirementsIndex().url },
]

const columns: ColumnDef<PurchaseRequirementRow>[] = [
    {
        accessorKey: 'name',
        header: 'Name',
    },
    {
        accessorKey: 'is_active',
        header: 'Status',
        cell: ({ row }) =>
            h(Badge, { variant: row.original.is_active ? 'default' : 'secondary' }, () =>
                row.original.is_active ? 'Active' : 'Inactive',
            ),
    },
    {
        id: 'associations',
        header: 'Associations',
        cell: ({ row }) => `${row.original.ticket_types_count} tickets, ${row.original.addons_count} addons`,
    },
]

const table = useVueTable({
    get data() {
        return props.requirements.data
    },
    columns,
    getCoreRowModel: getCoreRowModel(),
    manualPagination: true,
    rowCount: props.requirements.total,
    getRowId: (row) => String(row.id),
})

function setPage(page: number) {
    router.get(requirementsIndex().url, { page }, { preserveState: true })
}
</script>

<template>
    <Head title="Purchase Requirements" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 class="text-lg font-semibold">Purchase Requirements</h2>
                <Button as-child>
                    <Link :href="PurchaseRequirementController.create().url">
                        <Plus class="size-4" />
                        Create Requirement
                    </Link>
                </Button>
            </div>

            <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                <Table>
                    <TableHeader>
                        <TableRow v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
                            <TableHead v-for="header in headerGroup.headers" :key="header.id" class="px-4">
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
                                @click="router.visit(PurchaseRequirementController.edit(row.original.id).url)"
                            >
                                <TableCell v-for="cell in row.getVisibleCells()" :key="cell.id" class="px-4 py-3">
                                    <FlexRender :render="cell.column.columnDef.cell" :props="cell.getContext()" />
                                </TableCell>
                            </TableRow>
                        </template>
                        <TableEmpty v-else :colspan="columns.length">
                            No purchase requirements found.
                        </TableEmpty>
                    </TableBody>
                </Table>

                <div class="flex items-center justify-between border-t border-sidebar-border/70 px-4 py-3 dark:border-sidebar-border">
                    <span class="text-xs text-muted-foreground">
                        <template v-if="requirements.from && requirements.to">
                            Showing {{ requirements.from }}–{{ requirements.to }} of {{ requirements.total }}
                        </template>
                        <template v-else>{{ requirements.total }} requirements</template>
                    </span>
                    <div class="flex items-center gap-1">
                        <Button variant="outline" size="sm" class="size-8 p-0" :disabled="requirements.current_page <= 1" @click="setPage(requirements.current_page - 1)">
                            <ChevronLeft class="size-4" />
                        </Button>
                        <span class="px-2 text-xs text-muted-foreground">{{ requirements.current_page }} / {{ requirements.last_page }}</span>
                        <Button variant="outline" size="sm" class="size-8 p-0" :disabled="requirements.current_page >= requirements.last_page" @click="setPage(requirements.current_page + 1)">
                            <ChevronRight class="size-4" />
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
