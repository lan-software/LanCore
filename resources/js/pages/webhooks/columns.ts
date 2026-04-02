import type { ColumnDef } from '@tanstack/vue-table'
import { ArrowDown, ArrowUp, ArrowUpDown } from 'lucide-vue-next'
import { h } from 'vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import type { Webhook } from '@/types/domain'

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

const eventLabels: Record<string, string> = {
    'user.registered': 'User Registered',
    'announcement.published': 'Announcement Published',
    'news_article.published': 'News Article Published',
    'event.published': 'Event Published',
}

export const columns: ColumnDef<Webhook>[] = [
    {
        accessorKey: 'name',
        header: sortableHeader('Name'),
        cell: ({ row }) => {
            const name = row.getValue('name') as string
            const integration = row.original.integration_app

            return h('div', { class: 'flex items-center gap-2' }, [
                h('span', { class: 'font-medium' }, name),
                integration
                    ? h(Badge, { variant: 'outline', class: 'text-xs' }, () => `Integration: ${integration.name}`)
                    : null,
            ])
        },
    },
    {
        accessorKey: 'url',
        header: sortableHeader('URL'),
        cell: ({ row }) =>
            h('span', { class: 'text-muted-foreground font-mono text-xs truncate max-w-xs block' }, row.getValue('url')),
    },
    {
        accessorKey: 'event',
        header: () => h('span', 'Event'),
        cell: ({ row }) => {
            const event = row.getValue('event') as string

            return h(Badge, { variant: 'secondary' }, () => eventLabels[event] ?? event)
        },
    },
    {
        accessorKey: 'is_active',
        header: () => h('span', 'Status'),
        cell: ({ row }) => {
            const isActive = row.getValue('is_active') as boolean

            return isActive
                ? h(Badge, { variant: 'default' }, () => 'Active')
                : h(Badge, { variant: 'outline' }, () => 'Inactive')
        },
    },
    {
        accessorKey: 'deliveries_count',
        header: () => h('span', 'Sent'),
        cell: ({ row }) =>
            h('span', { class: 'tabular-nums' }, (row.getValue('deliveries_count') as number).toLocaleString()),
    },
    {
        id: 'health',
        header: () => h('span', 'Health'),
        cell: ({ row }) => {
            const code = row.original.last_delivery_status_code

            if (code === null) {
                return h(Badge, { variant: 'secondary' }, () => 'Unknown')
            }

            if (code >= 200 && code < 300) {
                return h(Badge, { class: 'bg-green-500/15 text-green-700 dark:text-green-400 border-green-500/20' }, () => 'Healthy')
            }

            return h(Badge, { class: 'bg-red-500/15 text-red-700 dark:text-red-400 border-red-500/20' }, () => `Unhealthy ${code}`)
        },
    },
    {
        accessorKey: 'created_at',
        header: sortableHeader('Created'),
        cell: ({ row }) =>
            h(
                'span',
                { class: 'text-muted-foreground' },
                new Date(row.getValue('created_at') as string).toLocaleDateString(undefined, {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric',
                }),
            ),
    },
]
