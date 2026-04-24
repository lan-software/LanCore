<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { CheckCircle } from 'lucide-vue-next';
import { useI18n } from 'vue-i18n';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { currencyFromCode, formatCents } from '@/lib/money';
import { index as shopIndex } from '@/routes/shop';
import { index as ticketsIndex } from '@/routes/tickets';
import type { Order } from '@/types/domain';

const props = defineProps<{
    order: Order;
}>();

const { t } = useI18n();
const orderCurrency = currencyFromCode(props.order.currency);

function formatPrice(cents: number): string {
    return formatCents(cents, orderCurrency);
}
</script>

<template>
    <Head :title="t('shop.checkoutSuccess.title')" />

    <div class="flex min-h-screen flex-col bg-background text-foreground">
        <header class="border-b">
            <div
                class="mx-auto flex max-w-5xl items-center justify-between px-6 py-4"
            >
                <Link href="/" class="text-lg font-semibold">LanCore</Link>
            </div>
        </header>

        <main class="flex flex-1 items-center justify-center px-6 py-12">
            <div class="max-w-lg space-y-6 text-center">
                <CheckCircle class="mx-auto size-16 text-green-500" />
                <h1 class="text-3xl font-bold">
                    {{ t('shop.checkoutSuccess.heading') }}
                </h1>
                <p class="text-muted-foreground">
                    {{ t('shop.checkoutSuccess.description') }}
                </p>

                <Card>
                    <CardHeader>
                        <CardTitle class="text-lg">{{
                            t('shop.orderSummary')
                        }}</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">{{
                                t('shop.subtotal')
                            }}</span>
                            <span>{{ formatPrice(order.subtotal) }}</span>
                        </div>
                        <div
                            v-if="order.discount > 0"
                            class="flex justify-between text-green-600"
                        >
                            <span>{{ t('shop.discount') }}</span>
                            <span>-{{ formatPrice(order.discount) }}</span>
                        </div>
                        <div
                            class="flex justify-between border-t pt-2 font-bold"
                        >
                            <span>{{ t('shop.total') }}</span>
                            <span>{{ formatPrice(order.total) }}</span>
                        </div>
                        <div v-if="order.tickets" class="pt-2">
                            <p class="text-muted-foreground">
                                {{
                                    t('shop.checkoutSuccess.ticketsPurchased', {
                                        count: order.tickets.length,
                                    })
                                }}
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <div class="flex items-center justify-center gap-4">
                    <Button as-child>
                        <Link :href="ticketsIndex().url">{{
                            t('shop.checkoutSuccess.viewMyTickets')
                        }}</Link>
                    </Button>
                    <Button variant="outline" as-child>
                        <Link :href="shopIndex().url">{{
                            t('shop.checkoutSuccess.backToShop')
                        }}</Link>
                    </Button>
                </div>
            </div>
        </main>

        <footer class="border-t">
            <div
                class="mx-auto max-w-5xl px-6 py-6 text-center text-sm text-muted-foreground"
            >
                {{ t('shop.poweredBy') }}
            </div>
        </footer>
    </div>
</template>
