import type { ColumnDef } from '@tanstack/vue-table';
import { ArrowDown, ArrowUp, ArrowUpDown, Check, X } from 'lucide-vue-next';
import { h } from 'vue';
import { Button } from '@/components/ui/button';
import type { Game } from '@/types/domain';

function sortableHeader(label: string) {
    return ({
        column,
    }: {
        column: {
            getToggleSortingHandler: () => ((e: Event) => void) | undefined;
            getIsSorted: () => false | 'asc' | 'desc';
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

export const columns: ColumnDef<Game>[] = [
    {
        accessorKey: 'name',
        header: sortableHeader('Name'),
        cell: ({ row }) =>
            h('span', { class: 'font-medium' }, row.getValue('name')),
    },
    {
        accessorKey: 'publisher',
        header: sortableHeader('Publisher'),
        cell: ({ row }) => h('span', row.getValue('publisher') ?? '—'),
    },
    {
        id: 'game_modes_count',
        header: () => h('span', 'Modes'),
        cell: ({ row }) =>
            h(
                'span',
                { class: 'text-muted-foreground' },
                String(row.original.game_modes_count ?? 0),
            ),
    },
    {
        id: 'is_active',
        header: () => h('span', 'Active'),
        cell: ({ row }) =>
            row.original.is_active
                ? h(Check, { class: 'size-4 text-green-600' })
                : h(X, { class: 'size-4 text-muted-foreground' }),
    },
    {
        accessorKey: 'created_at',
        header: sortableHeader('Created'),
        cell: ({ row }) => {
            const date = new Date(row.getValue('created_at') as string);

            return h(
                'span',
                { class: 'text-muted-foreground' },
                date.toLocaleDateString(),
            );
        },
    },
];
