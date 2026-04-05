import type { ColumnDef } from '@tanstack/vue-table';
import { ArrowDown, ArrowUp, ArrowUpDown } from 'lucide-vue-next';
import { h } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { GameServer } from '@/types/domain';

function sortableHeader(label: string) {
    return ({
        column,
    }: {
        column: {
            getToggleSortingHandler: () => void;
            getIsSorted: () => string | false;
        };
    }) =>
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
        );
}

const statusVariant: Record<string, string> = {
    available:
        'text-green-700 bg-green-50 dark:text-green-400 dark:bg-green-950',
    in_use: 'text-blue-700 bg-blue-50 dark:text-blue-400 dark:bg-blue-950',
    offline: 'text-red-700 bg-red-50 dark:text-red-400 dark:bg-red-950',
    maintenance:
        'text-yellow-700 bg-yellow-50 dark:text-yellow-400 dark:bg-yellow-950',
};

const allocationLabel: Record<string, string> = {
    competition: 'Competition',
    casual: 'Casual',
    flexible: 'Flexible',
};

export const columns: ColumnDef<GameServer>[] = [
    {
        accessorKey: 'name',
        header: sortableHeader('Name'),
        cell: ({ row }) =>
            h('span', { class: 'font-medium' }, row.getValue('name')),
    },
    {
        id: 'address',
        header: () => h('span', 'Address'),
        cell: ({ row }) =>
            h(
                'span',
                { class: 'font-mono text-sm' },
                `${row.original.host}:${row.original.port}`,
            ),
    },
    {
        id: 'game',
        header: () => h('span', 'Game'),
        cell: ({ row }) => h('span', {}, row.original.game?.name ?? '—'),
    },
    {
        accessorKey: 'status',
        header: sortableHeader('Status'),
        cell: ({ row }) => {
            const status = row.getValue('status') as string;

            return h(
                'span',
                {
                    class: `inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ${statusVariant[status] ?? ''}`,
                },
                status.replace('_', ' '),
            );
        },
    },
    {
        accessorKey: 'allocation_type',
        header: () => h('span', 'Allocation'),
        cell: ({ row }) => {
            const type = row.original.allocation_type;

            return h(
                Badge,
                { variant: 'outline' },
                () => allocationLabel[type] ?? type,
            );
        },
    },
];
