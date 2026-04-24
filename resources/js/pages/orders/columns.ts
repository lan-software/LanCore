import type { ColumnDef } from '@tanstack/vue-table';
import { ArrowDown, ArrowUp, ArrowUpDown } from 'lucide-vue-next';
import { h } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { currencyFromCode, formatCents } from '@/lib/money';
import type { Order } from '@/types/domain';

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

function formatOrderCurrency(order: Order): string {
    return formatCents(order.total, currencyFromCode(order.currency));
}

const statusVariant: Record<
    string,
    'default' | 'secondary' | 'destructive' | 'outline'
> = {
    completed: 'default',
    pending: 'outline',
    failed: 'destructive',
    refunded: 'secondary',
};

export const columns: ColumnDef<Order>[] = [
    {
        accessorKey: 'id',
        header: sortableHeader('ID'),
        cell: ({ row }) =>
            h(
                'span',
                { class: 'font-medium font-mono' },
                `#${row.getValue('id')}`,
            ),
    },
    {
        id: 'user',
        header: () => h('span', 'Customer'),
        cell: ({ row }) => {
            const user = row.original.user;

            return user
                ? h('div', { class: 'flex flex-col' }, [
                      h('span', { class: 'font-medium' }, user.name),
                      h(
                          'span',
                          { class: 'text-xs text-muted-foreground' },
                          user.email,
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
        accessorKey: 'status',
        header: sortableHeader('Status'),
        cell: ({ row }) => {
            const status = row.getValue<string>('status');
            const isAwaitingPayment =
                row.original.payment_method === 'on_site' &&
                row.original.paid_at === null;

            const label = status.charAt(0).toUpperCase() + status.slice(1);

            const badges = [
                h(
                    Badge,
                    { variant: statusVariant[status] ?? 'outline' },
                    () => label,
                ),
            ];

            if (isAwaitingPayment) {
                badges.push(
                    h(
                        Badge,
                        {
                            variant: 'outline',
                            class: 'ml-1 border-amber-500 text-amber-600',
                        },
                        () => 'Awaiting Payment',
                    ),
                );
            }

            return h('div', { class: 'flex items-center gap-1' }, badges);
        },
    },
    {
        accessorKey: 'payment_method',
        header: sortableHeader('Payment'),
        cell: ({ row }) => {
            const method = row.getValue<string>('payment_method');
            const label =
                method === 'stripe'
                    ? 'Credit Card'
                    : method === 'on_site'
                      ? 'On Site'
                      : method;

            return h('span', { class: 'text-muted-foreground' }, label);
        },
    },
    {
        accessorKey: 'total',
        header: sortableHeader('Total'),
        cell: ({ row }) =>
            h(
                'span',
                { class: 'font-medium' },
                formatOrderCurrency(row.original),
            ),
    },
    {
        accessorKey: 'created_at',
        header: sortableHeader('Date'),
        cell: ({ row }) =>
            h(
                'span',
                { class: 'text-muted-foreground text-xs' },
                formatDate(row.getValue('created_at') as string),
            ),
    },
];
