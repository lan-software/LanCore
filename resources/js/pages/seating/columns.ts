import { router } from '@inertiajs/vue3'
import type { ColumnDef } from '@tanstack/vue-table'
import { ArrowDown, ArrowUp, ArrowUpDown } from 'lucide-vue-next'
import { h } from 'vue'
import SeatPlanAuditController from '@/actions/App/Domain/Seating/Http/Controllers/SeatPlanAuditController'
import { Button } from '@/components/ui/button'
import type { SeatPlan } from '@/types/domain'

function sortableHeader(label: string) {
    return ({ column }: { column: { getToggleSortingHandler: () => ((e: Event) => void) | undefined; getIsSorted: () => false | 'asc' | 'desc' } }) =>
        h(
            Button,
            {
                variant: 'ghost',
                size: 'sm',
                class: '-ml-3 h-8 data-[state=open]:bg-accent',
                onClick: column.getToggleSortingHandler(),
            },
            () => [
                h('span', label),
                column.getIsSorted() === 'asc'
                    ? h(ArrowUp, { class: 'ml-1.5 size-3.5' })
                    : column.getIsSorted() === 'desc'
                      ? h(ArrowDown, { class: 'ml-1.5 size-3.5' })
                      : h(ArrowUpDown, { class: 'ml-1.5 size-3.5 opacity-40' }),
            ],
        )
}

export const columns: ColumnDef<SeatPlan>[] = [
    {
        accessorKey: 'name',
        header: sortableHeader('Name'),
        cell: ({ row }) => h('span', { class: 'font-medium' }, row.getValue('name')),
    },
    {
        id: 'event_name',
        header: sortableHeader('Event'),
        cell: ({ row }) => h('span', row.original.event?.name ?? '—'),
    },
    {
        id: 'blocks_count',
        header: () => h('span', 'Blocks'),
        cell: ({ row }) => h('span', { class: 'text-muted-foreground' }, String(row.original.data?.blocks?.length ?? 0)),
    },
    {
        accessorKey: 'created_at',
        header: sortableHeader('Created'),
        cell: ({ row }) => {
            const date = new Date(row.getValue('created_at') as string)

            return h('span', { class: 'text-muted-foreground' }, date.toLocaleDateString())
        },
    },
    {
        id: 'actions',
        header: () => h('span'),
        cell: ({ row }) =>
            h(
                Button,
                {
                    variant: 'outline',
                    size: 'sm',
                    onClick: (e: MouseEvent) => {
                        e.stopPropagation()
                        router.visit(SeatPlanAuditController(row.original.id).url)
                    },
                },
                () => 'Audit',
            ),
    },
]
