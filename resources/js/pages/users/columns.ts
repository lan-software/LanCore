import type { ColumnDef } from '@tanstack/vue-table'
import { ArrowDown, ArrowUp, ArrowUpDown } from 'lucide-vue-next'
import { h } from 'vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Checkbox } from '@/components/ui/checkbox'
import type { User } from '@/types/auth'

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

export const columns: ColumnDef<User>[] = [
    {
        id: 'select',
        enableSorting: false,
        enableHiding: false,
        header: ({ table }) =>
            h(Checkbox, {
                modelValue: table.getIsAllPageRowsSelected()
                    ? true
                    : table.getIsSomePageRowsSelected()
                      ? 'indeterminate'
                      : false,
                'onUpdate:modelValue': (value: boolean | 'indeterminate') =>
                    table.toggleAllPageRowsSelected(value === true),
                'aria-label': 'Select all',
            }),
        cell: ({ row }) =>
            h(Checkbox, {
                modelValue: row.getIsSelected(),
                'onUpdate:modelValue': (value: boolean | 'indeterminate') => row.toggleSelected(value === true),
                'aria-label': 'Select row',
            }),
    },
    {
        accessorKey: 'name',
        enableSorting: true,
        header: sortableHeader('Name'),
        cell: ({ row }) => h('span', { class: 'font-medium' }, row.getValue<string>('name')),
    },
    {
        accessorKey: 'email',
        enableSorting: true,
        header: sortableHeader('Email'),
        cell: ({ row }) => h('span', { class: 'text-muted-foreground' }, row.getValue<string>('email')),
    },
    {
        id: 'roles',
        accessorFn: (row) => row.roles,
        enableSorting: false,
        header: () => h('span', 'Roles'),
        cell: ({ row }) => {
            const roles = row.getValue<User['roles']>('roles')

            return h(
                'div',
                { class: 'flex flex-wrap gap-1' },
                roles.map((role) => h(Badge, { key: role.id, variant: 'outline' }, () => role.label)),
            )
        },
    },
    {
        accessorKey: 'created_at',
        enableSorting: true,
        header: sortableHeader('Joined'),
        cell: ({ row }) =>
            h(
                'span',
                { class: 'text-muted-foreground text-xs' },
                new Date(row.getValue<string>('created_at')).toLocaleDateString(),
            ),
    },
]
