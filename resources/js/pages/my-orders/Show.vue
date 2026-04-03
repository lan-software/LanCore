<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import UserOrderController from '@/actions/App/Domain/Shop/Http/Controllers/UserOrderController';
import TicketCard from '@/components/TicketCard.vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as myOrdersIndex } from '@/routes/my-orders';
import type { BreadcrumbItem } from '@/types';
import type { Order } from '@/types/domain';

const props = defineProps<{
    order: Order;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'My Orders', href: myOrdersIndex().url },
    {
        title: `Order #${props.order.id}`,
        href: UserOrderController.show(props.order.id).url,
    },
];

function formatCurrency(cents: number): string {
    return (cents / 100).toFixed(2) + ' \u20ac';
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

function paymentMethodLabel(method: string): string {
    return method === 'stripe'
        ? 'Credit Card (Stripe)'
        : method === 'on_site'
          ? 'Pay on Site'
          : method;
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
</script>

<template>
    <Head :title="`Order #${order.id}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full max-w-4xl flex-1 flex-col gap-6 p-4">
            <div>
                <Link
                    :href="myOrdersIndex().url"
                    class="text-sm text-muted-foreground hover:text-foreground"
                >
                    &larr; Back to My Orders
                </Link>
            </div>

            <!-- Order Summary -->
            <Card>
                <CardHeader>
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <CardTitle>Order #{{ order.id }}</CardTitle>
                            <p class="mt-1 text-sm text-muted-foreground">
                                Placed on {{ formatDate(order.created_at) }}
                            </p>
                        </div>
                        <div class="flex items-center gap-1">
                            <Badge
                                v-if="
                                    order.payment_method === 'on_site' &&
                                    order.paid_at === null
                                "
                                variant="outline"
                                class="border-amber-500 text-amber-600"
                            >
                                Pay on Site
                            </Badge>
                            <Badge
                                :variant="
                                    statusVariant[order.status] ?? 'outline'
                                "
                                class="capitalize"
                            >
                                {{ order.status }}
                            </Badge>
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <dl class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <dt class="text-muted-foreground">
                                Payment Method
                            </dt>
                            <dd class="mt-1">
                                {{ paymentMethodLabel(order.payment_method) }}
                            </dd>
                        </div>
                        <div v-if="order.event">
                            <dt class="text-muted-foreground">Event</dt>
                            <dd class="mt-1">{{ order.event.name }}</dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">Subtotal</dt>
                            <dd class="mt-1">
                                {{ formatCurrency(order.subtotal) }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">Discount</dt>
                            <dd class="mt-1">
                                {{
                                    order.discount > 0
                                        ? '\u2212' +
                                          formatCurrency(order.discount)
                                        : '\u2014'
                                }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">Total</dt>
                            <dd class="mt-1 font-semibold">
                                {{ formatCurrency(order.total) }}
                            </dd>
                        </div>
                    </dl>
                </CardContent>
            </Card>

            <!-- Order Lines -->
            <Card v-if="order.order_lines && order.order_lines.length > 0">
                <CardHeader>
                    <CardTitle>Items</CardTitle>
                </CardHeader>
                <CardContent class="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Item</TableHead>
                                <TableHead class="text-right">Qty</TableHead>
                                <TableHead class="text-right"
                                    >Unit Price</TableHead
                                >
                                <TableHead class="text-right">Total</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow
                                v-for="line in order.order_lines"
                                :key="line.id"
                            >
                                <TableCell class="font-medium">
                                    {{ line.description }}
                                </TableCell>
                                <TableCell class="text-right">
                                    {{ line.quantity }}
                                </TableCell>
                                <TableCell class="text-right">
                                    {{ formatCurrency(line.unit_price) }}
                                </TableCell>
                                <TableCell class="text-right">
                                    {{ formatCurrency(line.total_price) }}
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

            <!-- Tickets -->
            <div
                v-if="order.tickets && order.tickets.length > 0"
                class="space-y-3"
            >
                <h2 class="text-lg font-semibold">Tickets</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <TicketCard
                        v-for="ticket in order.tickets"
                        :key="ticket.id"
                        :ticket="ticket"
                    />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
