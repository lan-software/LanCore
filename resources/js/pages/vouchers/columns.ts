import { h } from 'vue'
import type { ColumnDef } from '@tanstack/vue-table'
import { ArrowDown, ArrowUp, ArrowUpDown } from 'lucide-vue-next'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import type { Voucher } from '@/types/domain'

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
    })
}

export const columns: ColumnDef<Voucher>[] = [
    {
        accessorKey: 'code',
        header: sortableHeader('Code'),
        cell: ({ row }) => h('span', { class: 'font-medium font-mono' }, row.getValue('code')),
    },
    {
        id: 'type',
        header: () => h('span', 'Type'),
        cell: ({ row }) => {
            const voucher = row.original
            if (voucher.type === 'FixedAmount') {
                return h('span', { class: 'text-muted-foreground' }, (voucher.discount_amount! / 100).toFixed(2) + ' €')
            }
            return h('span', { class: 'text-muted-foreground' }, voucher.discount_percent + '%')
        },
    },
    {
        id: 'usage',
        header: () => h('span', 'Usage'),
        cell: ({ row }) => {
            const voucher = row.original
            const max = voucher.max_uses !== null ? String(voucher.max_uses) : '∞'
            return h('span', { class: 'text-muted-foreground' }, `${voucher.times_used} / ${max}`)
        },
    },
    {
        id: 'event',
        header: () => h('span', 'Event'),
        cell: ({ row }) => h('span', row.original.event?.name ?? 'All events'),
    },
    {
        id: 'is_active',
        header: () => h('span', 'Status'),
        cell: ({ row }) =>
            h(
                Badge,
                { variant: row.original.is_active ? 'default' : 'secondary' },
                () => (row.original.is_active ? 'Active' : 'Inactive'),
            ),
    },
    {
        accessorKey: 'created_at',
        header: sortableHeader('Created'),
        cell: ({ row }) => h('span', { class: 'text-muted-foreground' }, formatDate(row.getValue('created_at') as string)),
    },
]
