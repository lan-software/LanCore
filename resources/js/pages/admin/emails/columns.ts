import type { ColumnDef } from '@tanstack/vue-table';
import { ArrowDown, ArrowUp, ArrowUpDown } from 'lucide-vue-next';
import { h } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

export type EmailMessageRow = {
    id: number;
    subject: string | null;
    status: 'queued' | 'sent' | 'failed' | 'bounced' | 'complained';
    source: string | null;
    source_label: string | null;
    from_address: string | null;
    to_addresses: { address: string; name: string | null }[] | null;
    sent_at: string | null;
    failed_at: string | null;
    created_at: string;
};

const statusVariant: Record<
    EmailMessageRow['status'],
    'default' | 'secondary' | 'destructive' | 'outline'
> = {
    queued: 'outline',
    sent: 'default',
    failed: 'destructive',
    bounced: 'destructive',
    complained: 'destructive',
};

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

export const columns: ColumnDef<EmailMessageRow>[] = [
    {
        accessorKey: 'created_at',
        enableSorting: true,
        header: sortableHeader('Sent'),
        cell: ({ row }) =>
            h(
                'span',
                { class: 'text-xs text-muted-foreground whitespace-nowrap' },
                new Date(row.getValue<string>('created_at')).toLocaleString(),
            ),
    },
    {
        id: 'recipient',
        accessorFn: (row) => row.to_addresses?.[0]?.address ?? '',
        enableSorting: false,
        header: () => h('span', 'Recipient'),
        cell: ({ row }) => {
            const addrs = row.original.to_addresses ?? [];
            const label =
                addrs.length === 0
                    ? '—'
                    : addrs.length === 1
                      ? addrs[0].address
                      : `${addrs[0].address} (+${addrs.length - 1})`;

            return h(
                'span',
                { class: 'text-sm whitespace-nowrap' },
                label,
            );
        },
    },
    {
        accessorKey: 'subject',
        enableSorting: true,
        header: sortableHeader('Subject'),
        cell: ({ row }) =>
            h(
                'span',
                { class: 'font-medium' },
                row.getValue<string | null>('subject') ?? '—',
            ),
    },
    {
        accessorKey: 'status',
        enableSorting: true,
        header: sortableHeader('Status'),
        cell: ({ row }) => {
            const status = row.getValue<EmailMessageRow['status']>('status');
            return h(
                Badge,
                { variant: statusVariant[status] },
                () => status,
            );
        },
    },
    {
        accessorKey: 'source',
        enableSorting: false,
        header: () => h('span', 'Source'),
        cell: ({ row }) => {
            const source = row.original.source;
            const label = row.original.source_label ?? source;

            return h(
                'span',
                { class: 'text-xs text-muted-foreground' },
                label
                    ? typeof label === 'string'
                        ? label.split('\\').pop()
                        : label
                    : '—',
            );
        },
    },
];
