import type { ColumnDef } from '@tanstack/vue-table'
import { ArrowDown, ArrowUp, ArrowUpDown } from 'lucide-vue-next'
import { h } from 'vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import type { Achievement } from '@/types/domain'

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

export const columns: ColumnDef<Achievement>[] = [
    {
        accessorKey: 'name',
        header: sortableHeader('Name'),
        cell: ({ row }) =>
            h('div', { class: 'flex items-center gap-2' }, [
                h('span', {
                    class: 'inline-block size-3 rounded-full',
                    style: { backgroundColor: row.original.color },
                }),
                h('span', { class: 'font-medium' }, row.getValue('name')),
            ]),
    },
    {
        accessorKey: 'icon',
        header: () => h('span', 'Icon'),
        cell: ({ row }) => h('span', { class: 'text-muted-foreground' }, row.getValue('icon')),
    },
    {
        accessorKey: 'is_active',
        header: sortableHeader('Status'),
        cell: ({ row }) => {
            const isActive = row.getValue('is_active') as boolean

            return h(
                Badge,
                { variant: isActive ? 'default' : 'secondary' },
                () => isActive ? 'Active' : 'Inactive',
            )
        },
    },
    {
        id: 'users_count',
        accessorKey: 'users_count',
        header: sortableHeader('Earned'),
        cell: ({ row }) => h('span', { class: 'text-muted-foreground' }, `${row.original.users_count ?? 0}`),
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
