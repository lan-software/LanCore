<script setup lang="ts">
import CartController from '@/actions/App/Domain/Shop/Http/Controllers/CartController'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card'
import type { Event, TicketAddon, TicketType } from '@/types/domain'
import { Head, Link, router } from '@inertiajs/vue3'
import { Calendar, MapPin, Plus, ShoppingCart } from 'lucide-vue-next'
import { dashboard, login } from '@/routes'
import { index as shopIndex } from '@/routes/shop'
import { ref } from 'vue'

const props = defineProps<{
    event: Event | null
    ticketTypes: TicketType[]
    addons: TicketAddon[]
    cartItemCount: number
}>()

function formatPrice(cents: number): string {
    return (cents / 100).toFixed(2) + ' €'
}

function formatDate(dateString: string): string {
    return new Date(dateString).toLocaleDateString(undefined, {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    })
}

const addingItem = ref<number | null>(null)

function addToCart(purchasableType: 'ticket_type' | 'addon', purchasableId: number) {
    if (!props.event) return

    addingItem.value = purchasableId

    router.post(
        CartController.addItem().url,
        {
            purchasable_type: purchasableType,
            purchasable_id: purchasableId,
            quantity: 1,
            event_id: props.event.id,
        },
        {
            preserveScroll: true,
            onFinish: () => {
                addingItem.value = null
            },
        },
    )
}
</script>

<template>
    <Head title="Shop" />

    <div class="flex min-h-screen flex-col bg-background text-foreground">
        <!-- Header -->
        <header class="border-b">
            <div class="mx-auto flex max-w-5xl items-center justify-between px-6 py-4">
                <Link href="/" class="text-lg font-semibold">LanCore</Link>
                <nav class="flex items-center gap-4">
                    <template v-if="$page.props.auth.user">
                        <Link :href="CartController.show().url" class="relative text-muted-foreground hover:text-foreground">
                            <ShoppingCart class="size-5" />
                            <Badge
                                v-if="cartItemCount > 0"
                                class="absolute -top-2 -right-2 flex size-5 items-center justify-center rounded-full p-0 text-xs"
                            >
                                {{ cartItemCount }}
                            </Badge>
                        </Link>
                        <Link
                            :href="dashboard()"
                            class="text-sm text-muted-foreground hover:text-foreground"
                        >
                            Dashboard
                        </Link>
                    </template>
                    <template v-else>
                        <Link :href="login()" class="text-sm text-muted-foreground hover:text-foreground">Log in</Link>
                    </template>
                </nav>
            </div>
        </header>

        <main class="flex-1">
            <template v-if="event">
                <div class="mx-auto max-w-5xl px-6 py-12">
                    <div class="space-y-8">
                        <!-- Event Info -->
                        <div>
                            <p class="text-sm font-medium uppercase tracking-wider text-muted-foreground">Ticket Shop</p>
                            <h1 class="mt-2 text-4xl font-bold tracking-tight">{{ event.name }}</h1>
                        </div>

                        <div class="flex flex-wrap gap-4 text-sm text-muted-foreground">
                            <div class="flex items-center gap-1.5">
                                <Calendar class="size-4" />
                                {{ formatDate(event.start_date) }}
                            </div>
                            <div v-if="event.venue" class="flex items-center gap-1.5">
                                <MapPin class="size-4" />
                                {{ event.venue.name }}
                            </div>
                        </div>

                        <!-- Ticket Types -->
                        <div class="space-y-4">
                            <h2 class="text-2xl font-semibold">Tickets</h2>
                            <div v-if="ticketTypes.length > 0" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                <Card v-for="tt in ticketTypes" :key="tt.id">
                                    <CardHeader>
                                        <div class="flex items-center justify-between">
                                            <CardTitle>{{ tt.name }}</CardTitle>
                                            <span class="text-lg font-bold">{{ formatPrice(tt.price) }}</span>
                                        </div>
                                        <CardDescription v-if="tt.description">{{ tt.description }}</CardDescription>
                                    </CardHeader>
                                    <CardContent>
                                        <div class="text-sm text-muted-foreground space-y-1">
                                            <p v-if="tt.ticket_category">Category: {{ tt.ticket_category.name }}</p>
                                            <p>{{ tt.seats_per_ticket }} seat(s) per ticket</p>
                                            <p v-if="tt.remaining_quota !== undefined">
                                                {{ tt.remaining_quota }} remaining
                                            </p>
                                        </div>
                                    </CardContent>
                                    <CardFooter v-if="$page.props.auth.user">
                                        <Button
                                            size="sm"
                                            :disabled="addingItem === tt.id || !tt.is_purchasable"
                                            @click="addToCart('ticket_type', tt.id)"
                                        >
                                            <Plus class="size-4" />
                                            {{ addingItem === tt.id ? 'Adding…' : 'Add to Cart' }}
                                        </Button>
                                    </CardFooter>
                                </Card>
                            </div>
                            <p v-else class="text-muted-foreground">No tickets available at this time.</p>
                        </div>

                        <!-- Addons -->
                        <div v-if="addons.length > 0" class="space-y-4">
                            <h2 class="text-2xl font-semibold">Addons</h2>
                            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                <Card v-for="addon in addons" :key="addon.id">
                                    <CardHeader>
                                        <div class="flex items-center justify-between">
                                            <CardTitle class="text-base">{{ addon.name }}</CardTitle>
                                            <span class="font-bold">{{ formatPrice(addon.price) }}</span>
                                        </div>
                                        <CardDescription v-if="addon.description">{{ addon.description }}</CardDescription>
                                    </CardHeader>
                                    <CardFooter v-if="$page.props.auth.user">
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            :disabled="addingItem === addon.id"
                                            @click="addToCart('addon', addon.id)"
                                        >
                                            <Plus class="size-4" />
                                            {{ addingItem === addon.id ? 'Adding…' : 'Add to Cart' }}
                                        </Button>
                                    </CardFooter>
                                </Card>
                            </div>
                        </div>

                        <!-- Login prompt -->
                        <div v-if="!$page.props.auth?.user" class="rounded-lg border p-6 text-center space-y-3">
                            <p class="text-muted-foreground">Please log in to purchase tickets.</p>
                            <Button as-child>
                                <Link :href="login()">Log in</Link>
                            </Button>
                        </div>
                    </div>
                </div>
            </template>

            <template v-else>
                <div class="flex flex-1 items-center justify-center px-6 py-24">
                    <div class="text-center space-y-4">
                        <ShoppingCart class="mx-auto size-12 text-muted-foreground" />
                        <h1 class="text-3xl font-bold tracking-tight">No Tickets Available</h1>
                        <p class="text-muted-foreground max-w-md mx-auto">
                            There are currently no events with tickets available for purchase.
                        </p>
                    </div>
                </div>
            </template>
        </main>

        <footer class="border-t">
            <div class="mx-auto max-w-5xl px-6 py-6 text-center text-sm text-muted-foreground">
                Powered by LanCore
            </div>
        </footer>
    </div>
</template>
