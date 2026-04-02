import { router } from '@inertiajs/vue3'
import type { ColumnDef } from '@tanstack/vue-table'
import { ArrowDown, ArrowUp, ArrowUpDown } from 'lucide-vue-next'
import { h } from 'vue'
import AddonAuditController from '@/actions/App/Domain/Ticketing/Http/Controllers/AddonAuditController'
import { Button } from '@/components/ui/button'
import type { TicketAddon } from '@/types/domain'

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

function formatDate(dateString: string): string {
    return new Date(dateString).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    })
}

export const columns: ColumnDef<TicketAddon>[] = [
    {
        accessorKey: 'name',
        header: sortableHeader('Name'),
        cell: ({ row }) => h('span', { class: 'font-medium' }, row.getValue('name')),
    },
    {
        accessorKey: 'price',
        header: sortableHeader('Price'),
        cell: ({ row }) => h('span', { class: 'text-muted-foreground' }, (Number(row.getValue('price')) / 100).toFixed(2) + ' €'),
    },
    {
        accessorKey: 'quota',
        header: () => h('span', 'Quota'),
        cell: ({ row }) => h('span', { class: 'text-muted-foreground' }, row.getValue('quota') != null ? String(row.getValue('quota')) : '∞'),
    },
    {
        id: 'seats_consumed',
        accessorKey: 'seats_consumed',
        header: () => h('span', 'Seats'),
        cell: ({ row }) => h('span', { class: 'text-muted-foreground' }, String(row.original.seats_consumed)),
    },
    {
        id: 'event',
        header: () => h('span', 'Event'),
        cell: ({ row }) => h('span', row.original.event?.name ?? '—'),
    },
    {
        accessorKey: 'created_at',
        header: sortableHeader('Created'),
        cell: ({ row }) => h('span', { class: 'text-muted-foreground' }, formatDate(row.getValue('created_at') as string)),
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
                        router.visit(AddonAuditController(row.original.id).url)
                    },
                },
                () => 'Audit',
            ),
    },
]
