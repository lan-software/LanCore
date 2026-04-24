<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { edit as eventEdit } from '@/actions/App/Domain/Event/Http/Controllers/EventController';
import OrderController from '@/actions/App/Domain/Shop/Http/Controllers/OrderController';
import { show as adminTicketShow } from '@/actions/App/Domain/Ticketing/Http/Controllers/AdminTicketController';
import { edit as ticketTypeEdit } from '@/actions/App/Domain/Ticketing/Http/Controllers/TicketTypeController';
import { show as userShow } from '@/actions/App/Http/Controllers/Users/UserController';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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
import { currencyFromCode, formatCents } from '@/lib/money';
import { index as ordersIndex } from '@/routes/orders';
import type { BreadcrumbItem } from '@/types';
import type { Order } from '@/types/domain';

const props = defineProps<{
    order: Order;
}>();

const orderCurrency = currencyFromCode(props.order.currency);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: ordersIndex().url },
    { title: 'Orders', href: ordersIndex().url },
    {
        title: `Order #${props.order.id}`,
        href: OrderController.show(props.order.id).url,
    },
];

function formatCurrency(cents: number): string {
    return formatCents(cents, orderCurrency);
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
                    :href="ordersIndex().url"
                    class="text-sm text-muted-foreground hover:text-foreground"
                >
                    &larr; Back to Orders
                </Link>
            </div>

            <Heading
                :title="`Order #${order.id}`"
                :description="`Placed on ${formatDate(order.created_at)}`"
            />

            <!-- Order Summary -->
            <Card>
                <CardHeader>
                    <CardTitle>Order Details</CardTitle>
                </CardHeader>
                <CardContent>
                    <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <dt
                                class="text-sm font-medium text-muted-foreground"
                            >
                                Status
                            </dt>
                            <dd class="mt-1">
                                <Badge
                                    :variant="
                                        statusVariant[order.status] ?? 'outline'
                                    "
                                    class="capitalize"
                                    >{{ order.status }}</Badge
                                >
                                <Button
                                    v-if="
                                        order.payment_method === 'on_site' &&
                                        order.paid_at === null
                                    "
                                    size="sm"
                                    class="ml-2"
                                    @click="
                                        router.patch(
                                            OrderController.confirmPayment(
                                                order.id,
                                            ).url,
                                            {},
                                            { preserveScroll: true },
                                        )
                                    "
                                >
                                    Confirm Payment Received
                                </Button>
                            </dd>
                        </div>
                        <div>
                            <dt
                                class="text-sm font-medium text-muted-foreground"
                            >
                                Payment Method
                            </dt>
                            <dd class="mt-1 text-sm">
                                {{ paymentMethodLabel(order.payment_method) }}
                            </dd>
                        </div>
                        <div>
                            <dt
                                class="text-sm font-medium text-muted-foreground"
                            >
                                Subtotal
                            </dt>
                            <dd class="mt-1 text-sm">
                                {{ formatCurrency(order.subtotal) }}
                            </dd>
                        </div>
                        <div>
                            <dt
                                class="text-sm font-medium text-muted-foreground"
                            >
                                Discount
                            </dt>
                            <dd class="mt-1 text-sm">
                                {{
                                    order.discount > 0
                                        ? '−' + formatCurrency(order.discount)
                                        : '—'
                                }}
                            </dd>
                        </div>
                        <div>
                            <dt
                                class="text-sm font-medium text-muted-foreground"
                            >
                                Total
                            </dt>
                            <dd class="mt-1 text-sm font-semibold">
                                {{ formatCurrency(order.total) }}
                            </dd>
                        </div>
                        <div v-if="order.provider_transaction_id">
                            <dt
                                class="text-sm font-medium text-muted-foreground"
                            >
                                Transaction ID
                            </dt>
                            <dd class="mt-1 font-mono text-sm">
                                {{ order.provider_transaction_id }}
                            </dd>
                        </div>
                    </dl>
                </CardContent>
            </Card>

            <!-- Customer -->
            <Card v-if="order.user">
                <CardHeader>
                    <CardTitle>Customer</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-medium">{{ order.user.name }}</p>
                            <p class="text-sm text-muted-foreground">
                                {{ order.user.email }}
                            </p>
                        </div>
                        <Button variant="outline" size="sm" as-child>
                            <Link :href="userShow(order.user.id).url"
                                >View User</Link
                            >
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Event -->
            <Card v-if="order.event">
                <CardHeader>
                    <CardTitle>Event</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="flex items-center justify-between">
                        <p class="font-medium">{{ order.event.name }}</p>
                        <Button variant="outline" size="sm" as-child>
                            <Link :href="eventEdit(order.event.id).url"
                                >View Event</Link
                            >
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Voucher -->
            <Card v-if="order.voucher">
                <CardHeader>
                    <CardTitle>Voucher</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-mono font-medium">
                                {{ order.voucher.code }}
                            </p>
                            <p class="text-sm text-muted-foreground">
                                <template
                                    v-if="order.voucher.type === 'fixed_amount'"
                                >
                                    {{
                                        formatCurrency(
                                            order.voucher.discount_amount!,
                                        )
                                    }}
                                    off
                                </template>
                                <template v-else>
                                    {{ order.voucher.discount_percent }}% off
                                </template>
                            </p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Order Lines -->
            <Card v-if="order.order_lines && order.order_lines.length > 0">
                <CardHeader>
                    <CardTitle>Order Lines</CardTitle>
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
                                <TableCell class="font-medium">{{
                                    line.description
                                }}</TableCell>
                                <TableCell class="text-right">{{
                                    line.quantity
                                }}</TableCell>
                                <TableCell class="text-right">{{
                                    formatCurrency(line.unit_price)
                                }}</TableCell>
                                <TableCell class="text-right">{{
                                    formatCurrency(line.total_price)
                                }}</TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

            <!-- Tickets -->
            <Card v-if="order.tickets && order.tickets.length > 0">
                <CardHeader>
                    <CardTitle>Tickets</CardTitle>
                </CardHeader>
                <CardContent class="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Ticket</TableHead>
                                <TableHead>Type</TableHead>
                                <TableHead>Owner</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead class="text-right"
                                    >Actions</TableHead
                                >
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow
                                v-for="ticket in order.tickets"
                                :key="ticket.id"
                            >
                                <TableCell class="font-mono text-sm"
                                    >#{{ ticket.id }}</TableCell
                                >
                                <TableCell>
                                    <Link
                                        v-if="ticket.ticket_type"
                                        :href="
                                            ticketTypeEdit(
                                                ticket.ticket_type_id,
                                            ).url
                                        "
                                        class="text-primary hover:underline"
                                    >
                                        {{ ticket.ticket_type.name }}
                                    </Link>
                                    <span v-else class="text-muted-foreground"
                                        >—</span
                                    >
                                </TableCell>
                                <TableCell>
                                    <Link
                                        v-if="ticket.owner"
                                        :href="userShow(ticket.owner.id).url"
                                        class="text-primary hover:underline"
                                    >
                                        {{ ticket.owner.name }}
                                    </Link>
                                    <span v-else class="text-muted-foreground"
                                        >—</span
                                    >
                                </TableCell>
                                <TableCell>
                                    <Badge
                                        :variant="
                                            ticket.status === 'Active'
                                                ? 'default'
                                                : ticket.status === 'Cancelled'
                                                  ? 'destructive'
                                                  : 'secondary'
                                        "
                                    >
                                        {{ ticket.status }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="text-right">
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        as-child
                                    >
                                        <Link
                                            :href="
                                                adminTicketShow(ticket.id).url
                                            "
                                            >View</Link
                                        >
                                    </Button>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
