import { router } from '@inertiajs/vue3';
import type { ColumnDef } from '@tanstack/vue-table';
import { ArrowDown, ArrowUp, ArrowUpDown } from 'lucide-vue-next';
import { h } from 'vue';
import TicketTypeAuditController from '@/actions/App/Domain/Ticketing/Http/Controllers/TicketTypeAuditController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { TicketType } from '@/types/domain';

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

function formatDate(dateString: string): string {
    return new Date(dateString).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

export const columns: ColumnDef<TicketType>[] = [
    {
        accessorKey: 'name',
        header: sortableHeader('Name'),
        cell: ({ row }) =>
            h('span', { class: 'font-medium' }, row.getValue('name')),
    },
    {
        accessorKey: 'price',
        header: sortableHeader('Price'),
        cell: ({ row }) =>
            h(
                'span',
                { class: 'text-muted-foreground' },
                (Number(row.getValue('price')) / 100).toFixed(2) + ' €',
            ),
    },
    {
        accessorKey: 'quota',
        header: sortableHeader('Quota'),
        cell: ({ row }) =>
            h(
                'span',
                { class: 'text-muted-foreground' },
                String(row.getValue('quota')),
            ),
    },
    {
        id: 'event',
        header: () => h('span', 'Event'),
        cell: ({ row }) => h('span', row.original.event?.name ?? '—'),
    },
    {
        id: 'category',
        header: () => h('span', 'Category'),
        cell: ({ row }) => h('span', row.original.ticket_category?.name ?? '—'),
    },
    {
        id: 'is_locked',
        header: () => h('span', 'Status'),
        cell: ({ row }) =>
            h(
                Badge,
                { variant: row.original.is_locked ? 'default' : 'secondary' },
                () => (row.original.is_locked ? 'Locked' : 'Open'),
            ),
    },
    {
        accessorKey: 'created_at',
        header: sortableHeader('Created'),
        cell: ({ row }) =>
            h(
                'span',
                { class: 'text-muted-foreground' },
                formatDate(row.getValue('created_at') as string),
            ),
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
                        e.stopPropagation();
                        router.visit(
                            TicketTypeAuditController(row.original.id).url,
                        );
                    },
                },
                () => 'Audit',
            ),
    },
];
