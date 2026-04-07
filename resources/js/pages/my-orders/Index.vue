<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Clock, ShoppingCart } from 'lucide-vue-next';
import UserOrderController from '@/actions/App/Domain/Shop/Http/Controllers/UserOrderController';
import EventSelector from '@/components/EventSelector.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as myOrdersIndex } from '@/routes/my-orders';
import { index as shopIndex } from '@/routes/shop';
import type { BreadcrumbItem } from '@/types';
import type { Order } from '@/types/domain';

defineProps<{
    orders: Order[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'My Orders', href: myOrdersIndex().url },
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

function statusVariant(
    status: string,
): 'default' | 'secondary' | 'destructive' | 'outline' {
    switch (status) {
        case 'completed':
            return 'default';
        case 'pending':
            return 'outline';
        case 'failed':
            return 'destructive';
        case 'refunded':
            return 'secondary';
        default:
            return 'outline';
    }
}

function paymentMethodLabel(method: string): string {
    return method === 'stripe'
        ? 'Credit Card (Stripe)'
        : method === 'on_site'
          ? 'Pay on Site'
          : method;
}
</script>

<template>
    <Head title="My Orders" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">My Orders</h1>
                    <p class="text-sm text-muted-foreground">
                        View your order history and payment status
                    </p>
                </div>
                <Button as-child>
                    <Link :href="shopIndex().url">
                        <ShoppingCart class="size-4" />
                        Shop
                    </Link>
                </Button>
            </div>

            <EventSelector variant="my" :sidebar="false" />

            <div
                v-if="orders.length > 0"
                class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3"
            >
                <Link
                    v-for="order in orders"
                    :key="order.id"
                    :href="UserOrderController.show(order.id).url"
                    class="block transition-shadow hover:shadow-md"
                >
                    <Card>
                        <CardHeader class="pb-2">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0 flex-1">
                                    <CardTitle class="text-base">
                                        Order #{{ order.id }}
                                    </CardTitle>
                                    <CardDescription v-if="order.event">
                                        {{ order.event.name }}
                                    </CardDescription>
                                </div>
                                <div class="flex shrink-0 items-center gap-1">
                                    <Badge
                                        v-if="
                                            order.payment_method ===
                                                'on_site' &&
                                            order.paid_at === null
                                        "
                                        variant="outline"
                                        class="border-amber-500 text-amber-600"
                                    >
                                        Pay on Site
                                    </Badge>
                                    <Badge
                                        :variant="statusVariant(order.status)"
                                        class="capitalize"
                                    >
                                        {{ order.status }}
                                    </Badge>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div
                                class="flex items-center justify-between text-sm"
                            >
                                <span class="text-muted-foreground">
                                    {{
                                        paymentMethodLabel(order.payment_method)
                                    }}
                                </span>
                                <span class="font-semibold">
                                    {{ formatCurrency(order.total) }}
                                </span>
                            </div>
                            <div
                                class="mt-2 flex items-center justify-between text-xs text-muted-foreground"
                            >
                                <span>{{ formatDate(order.created_at) }}</span>
                                <span v-if="order.tickets">
                                    {{ order.tickets.length }} ticket(s)
                                </span>
                            </div>
                        </CardContent>
                    </Card>
                </Link>
            </div>

            <div v-else class="py-12 text-center">
                <Clock class="mx-auto mb-4 size-12 text-muted-foreground" />
                <p class="text-muted-foreground">
                    You haven't placed any orders yet.
                </p>
                <Button as-child class="mt-4">
                    <Link :href="shopIndex().url">Browse the Shop</Link>
                </Button>
            </div>
        </div>
    </AppLayout>
</template>
