import type { ColumnDef } from '@tanstack/vue-table';
import { ArrowDown, ArrowUp, ArrowUpDown } from 'lucide-vue-next';
import { h } from 'vue';
import { Button } from '@/components/ui/button';
import type { OrchestrationJob } from '@/types/domain';

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

const statusColors: Record<string, string> = {
    pending: 'text-gray-700 bg-gray-50 dark:text-gray-400 dark:bg-gray-900',
    selecting_server:
        'text-yellow-700 bg-yellow-50 dark:text-yellow-400 dark:bg-yellow-950',
    deploying: 'text-blue-700 bg-blue-50 dark:text-blue-400 dark:bg-blue-950',
    active: 'text-green-700 bg-green-50 dark:text-green-400 dark:bg-green-950',
    completed:
        'text-emerald-700 bg-emerald-50 dark:text-emerald-400 dark:bg-emerald-950',
    failed: 'text-red-700 bg-red-50 dark:text-red-400 dark:bg-red-950',
    cancelled: 'text-gray-500 bg-gray-50 dark:text-gray-500 dark:bg-gray-900',
};

export const columns: ColumnDef<OrchestrationJob>[] = [
    {
        accessorKey: 'id',
        header: () => h('span', '#'),
        cell: ({ row }) =>
            h('span', { class: 'font-mono text-sm' }, `#${row.original.id}`),
    },
    {
        id: 'match',
        header: () => h('span', 'Match'),
        cell: ({ row }) =>
            h(
                'span',
                { class: 'font-mono text-sm' },
                `Match ${row.original.lanbrackets_match_id}`,
            ),
    },
    {
        id: 'competition',
        header: () => h('span', 'Competition'),
        cell: ({ row }) => h('span', {}, row.original.competition?.name ?? '—'),
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
                    class: `inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ${statusColors[status] ?? ''}`,
                },
                status.replace(/_/g, ' '),
            );
        },
    },
    {
        id: 'server',
        header: () => h('span', 'Server'),
        cell: ({ row }) =>
            row.original.game_server
                ? h(
                      'span',
                      { class: 'font-mono text-sm' },
                      row.original.game_server.name,
                  )
                : h('span', { class: 'text-muted-foreground' }, '—'),
    },
    {
        accessorKey: 'created_at',
        header: sortableHeader('Created'),
        cell: ({ row }) => {
            const date = new Date(row.getValue('created_at') as string);

            return h(
                'span',
                { class: 'text-sm text-muted-foreground' },
                date.toLocaleString(),
            );
        },
    },
];
