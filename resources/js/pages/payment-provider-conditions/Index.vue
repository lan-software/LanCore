<script setup lang="ts">
import { FlexRender, getCoreRowModel, useVueTable, type ColumnDef } from '@tanstack/vue-table'
import { router, Head, Link } from '@inertiajs/vue3'
import PaymentProviderConditionController from '@/actions/App/Domain/Shop/Http/Controllers/PaymentProviderConditionController'
import { ChevronLeft, ChevronRight, Plus } from 'lucide-vue-next'
import AppLayout from '@/layouts/AppLayout.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Table, TableBody, TableCell, TableEmpty, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { index as conditionsIndex } from '@/routes/payment-provider-conditions'
import type { BreadcrumbItem } from '@/types'
import { h } from 'vue'

type ConditionRow = {
    id: number
    payment_method: string
    name: string
    acknowledgement_label: string
    is_required: boolean
    is_active: boolean
    sort_order: number
}

interface Paginated {
    data: ConditionRow[]
    current_page: number
    last_page: number
    per_page: number
    total: number
    from: number | null
    to: number | null
}

const props = defineProps<{
    conditions: Paginated
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: conditionsIndex().url },
    { title: 'Payment Provider Conditions', href: conditionsIndex().url },
]

const columns: ColumnDef<ConditionRow>[] = [
    { accessorKey: 'name', header: 'Name' },
    {
        accessorKey: 'payment_method',
        header: 'Payment Method',
        cell: ({ row }) => h(Badge, { variant: 'outline' }, () => row.original.payment_method),
    },
    { accessorKey: 'acknowledgement_label', header: 'Acknowledgement' },
    {
        accessorKey: 'is_active',
        header: 'Status',
        cell: ({ row }) =>
            h(Badge, { variant: row.original.is_active ? 'default' : 'secondary' }, () =>
                row.original.is_active ? 'Active' : 'Inactive',
            ),
    },
]

const table = useVueTable({
    get data() {
        return props.conditions.data
    },
    columns,
    getCoreRowModel: getCoreRowModel(),
    manualPagination: true,
    rowCount: props.conditions.total,
    getRowId: (row) => String(row.id),
})

function setPage(page: number) {
    router.get(conditionsIndex().url, { page }, { preserveState: true })
}
</script>

<template>
    <Head title="Payment Provider Conditions" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 class="text-lg font-semibold">Payment Provider Conditions</h2>
                <Button as-child>
                    <Link :href="PaymentProviderConditionController.create().url">
                        <Plus class="size-4" />
                        Create Condition
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
                                @click="router.visit(PaymentProviderConditionController.edit(row.original.id).url)"
                            >
                                <TableCell v-for="cell in row.getVisibleCells()" :key="cell.id" class="px-4 py-3">
                                    <FlexRender :render="cell.column.columnDef.cell" :props="cell.getContext()" />
                                </TableCell>
                            </TableRow>
                        </template>
                        <TableEmpty v-else :colspan="columns.length">
                            No payment provider conditions found.
                        </TableEmpty>
                    </TableBody>
                </Table>

                <div class="flex items-center justify-between border-t border-sidebar-border/70 px-4 py-3 dark:border-sidebar-border">
                    <span class="text-xs text-muted-foreground">{{ conditions.total }} conditions</span>
                    <div class="flex items-center gap-1">
                        <Button variant="outline" size="sm" class="size-8 p-0" :disabled="conditions.current_page <= 1" @click="setPage(conditions.current_page - 1)">
                            <ChevronLeft class="size-4" />
                        </Button>
                        <span class="px-2 text-xs text-muted-foreground">{{ conditions.current_page }} / {{ conditions.last_page }}</span>
                        <Button variant="outline" size="sm" class="size-8 p-0" :disabled="conditions.current_page >= conditions.last_page" @click="setPage(conditions.current_page + 1)">
                            <ChevronRight class="size-4" />
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
