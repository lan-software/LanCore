import type { ColumnDef } from '@tanstack/vue-table';
import { ArrowDown, ArrowUp, ArrowUpDown } from 'lucide-vue-next';
import { h } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { Ticket } from '@/types/domain';

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

const statusVariant: Record<
    string,
    'default' | 'secondary' | 'destructive' | 'outline'
> = {
    Active: 'default',
    CheckedIn: 'secondary',
    Cancelled: 'destructive',
};

export const columns: ColumnDef<Ticket>[] = [
    {
        accessorKey: 'validation_id',
        header: sortableHeader('Validation ID'),
        cell: ({ row }) =>
            h(
                'span',
                { class: 'font-medium font-mono text-sm' },
                row.getValue('validation_id'),
            ),
    },
    {
        id: 'ticket_type',
        header: () => h('span', 'Type'),
        cell: ({ row }) =>
            h(
                'span',
                { class: 'text-muted-foreground' },
                row.original.ticket_type?.name ?? '—',
            ),
    },
    {
        id: 'owner',
        header: () => h('span', 'Owner'),
        cell: ({ row }) => {
            const owner = row.original.owner;

            return owner
                ? h('div', { class: 'flex flex-col' }, [
                      h('span', { class: 'font-medium' }, owner.name),
                      h(
                          'span',
                          { class: 'text-xs text-muted-foreground' },
                          owner.email,
                      ),
                  ])
                : h('span', { class: 'text-muted-foreground' }, '—');
        },
    },
    {
        id: 'event',
        header: () => h('span', 'Event'),
        cell: ({ row }) =>
            h(
                'span',
                { class: 'text-muted-foreground' },
                row.original.event?.name ?? '—',
            ),
    },
    {
        id: 'order',
        header: () => h('span', 'Order'),
        cell: ({ row }) =>
            row.original.order_id
                ? h(
                      'span',
                      { class: 'font-mono text-sm text-muted-foreground' },
                      `#${row.original.order_id}`,
                  )
                : h('span', { class: 'text-muted-foreground' }, '—'),
    },
    {
        accessorKey: 'status',
        header: sortableHeader('Status'),
        cell: ({ row }) => {
            const status = row.getValue<string>('status');

            return h(
                Badge,
                { variant: statusVariant[status] ?? 'outline' },
                () => status,
            );
        },
    },
    {
        accessorKey: 'created_at',
        header: sortableHeader('Created'),
        cell: ({ row }) =>
            h(
                'span',
                { class: 'text-muted-foreground text-xs' },
                formatDate(row.getValue('created_at') as string),
            ),
    },
];
