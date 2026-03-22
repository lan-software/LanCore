import { h } from 'vue'
import type { ColumnDef } from '@tanstack/vue-table'
import { ArrowDown, ArrowUp, ArrowUpDown } from 'lucide-vue-next'
import { router } from '@inertiajs/vue3'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import EventAuditController from '@/actions/App/Domain/Event/Http/Controllers/EventAuditController'
import type { Event } from '@/types/domain'

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

export const columns: ColumnDef<Event>[] = [
    {
        accessorKey: 'name',
        header: sortableHeader('Name'),
        cell: ({ row }) => h('span', { class: 'font-medium' }, row.getValue('name')),
    },
    {
        accessorKey: 'start_date',
        header: sortableHeader('Start'),
        cell: ({ row }) => h('span', { class: 'text-muted-foreground' }, formatDate(row.getValue('start_date') as string)),
    },
    {
        accessorKey: 'end_date',
        header: sortableHeader('End'),
        cell: ({ row }) => h('span', { class: 'text-muted-foreground' }, formatDate(row.getValue('end_date') as string)),
    },
    {
        id: 'venue',
        header: () => h('span', 'Venue'),
        cell: ({ row }) => h('span', row.original.venue?.name ?? '—'),
    },
    {
        accessorKey: 'status',
        header: sortableHeader('Status'),
        cell: ({ row }) => {
            const status = row.getValue('status') as string
            return h(
                Badge,
                { variant: status === 'published' ? 'default' : 'secondary' },
                () => status.charAt(0).toUpperCase() + status.slice(1),
            )
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
                        router.visit(EventAuditController(row.original.id).url)
                    },
                },
                () => 'Audit',
            ),
    },
]
