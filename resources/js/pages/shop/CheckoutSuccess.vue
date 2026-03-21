<script setup lang="ts">
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import type { Order } from '@/types/domain'
import { Head, Link } from '@inertiajs/vue3'
import { index as ticketsIndex } from '@/routes/tickets'
import { index as shopIndex } from '@/routes/shop'
import { CheckCircle } from 'lucide-vue-next'

defineProps<{
    order: Order
}>()

function formatPrice(cents: number): string {
    return (cents / 100).toFixed(2) + ' €'
}
</script>

<template>
    <Head title="Order Confirmed" />

    <div class="flex min-h-screen flex-col bg-background text-foreground">
        <header class="border-b">
            <div class="mx-auto flex max-w-5xl items-center justify-between px-6 py-4">
                <Link href="/" class="text-lg font-semibold">LanCore</Link>
            </div>
        </header>

        <main class="flex-1 flex items-center justify-center px-6 py-12">
            <div class="text-center space-y-6 max-w-lg">
                <CheckCircle class="mx-auto size-16 text-green-500" />
                <h1 class="text-3xl font-bold">Order Confirmed!</h1>
                <p class="text-muted-foreground">
                    Your tickets have been purchased successfully. You can manage them from your tickets page.
                </p>

                <Card>
                    <CardHeader>
                        <CardTitle class="text-lg">Order Summary</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Subtotal</span>
                            <span>{{ formatPrice(order.subtotal) }}</span>
                        </div>
                        <div v-if="order.discount > 0" class="flex justify-between text-green-600">
                            <span>Discount</span>
                            <span>-{{ formatPrice(order.discount) }}</span>
                        </div>
                        <div class="flex justify-between border-t pt-2 font-bold">
                            <span>Total</span>
                            <span>{{ formatPrice(order.total) }}</span>
                        </div>
                        <div v-if="order.tickets" class="pt-2">
                            <p class="text-muted-foreground">{{ order.tickets.length }} ticket(s) purchased</p>
                        </div>
                    </CardContent>
                </Card>

                <div class="flex items-center justify-center gap-4">
                    <Button as-child>
                        <Link :href="ticketsIndex().url">View My Tickets</Link>
                    </Button>
                    <Button variant="outline" as-child>
                        <Link :href="shopIndex().url">Back to Shop</Link>
                    </Button>
                </div>
            </div>
        </main>

        <footer class="border-t">
            <div class="mx-auto max-w-5xl px-6 py-6 text-center text-sm text-muted-foreground">
                Powered by LanCore
            </div>
        </footer>
    </div>
</template>
