import { h } from 'vue'
import type { ColumnDef } from '@tanstack/vue-table'
import { ArrowDown, ArrowUp, ArrowUpDown } from 'lucide-vue-next'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import type { Announcement } from '@/types/domain'

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

const priorityVariant: Record<string, 'default' | 'secondary' | 'destructive' | 'outline'> = {
    emergency: 'destructive',
    normal: 'default',
    silent: 'secondary',
}

export const columns: ColumnDef<Announcement>[] = [
    {
        accessorKey: 'title',
        header: sortableHeader('Title'),
        cell: ({ row }) => h('span', { class: 'font-medium' }, row.getValue('title')),
    },
    {
        accessorKey: 'priority',
        header: sortableHeader('Priority'),
        cell: ({ row }) => {
            const priority = row.getValue('priority') as string
            return h(
                Badge,
                { variant: priorityVariant[priority] ?? 'outline' },
                () => priority.charAt(0).toUpperCase() + priority.slice(1),
            )
        },
    },
    {
        id: 'event',
        header: () => h('span', 'Event'),
        cell: ({ row }) => h('span', row.original.event?.name ?? '—'),
    },
    {
        id: 'author',
        header: () => h('span', 'Author'),
        cell: ({ row }) => h('span', row.original.author?.name ?? '—'),
    },
    {
        accessorKey: 'published_at',
        header: sortableHeader('Published'),
        cell: ({ row }) => {
            const date = row.getValue('published_at') as string | null
            return date
                ? h(
                      'span',
                      { class: 'text-muted-foreground' },
                      new Date(date).toLocaleDateString(undefined, {
                          year: 'numeric',
                          month: 'short',
                          day: 'numeric',
                      }),
                  )
                : h(Badge, { variant: 'outline' }, () => 'Draft')
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
