<script setup lang="ts">
import TicketController from '@/actions/App/Domain/Ticketing/Http/Controllers/TicketController'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import AppLayout from '@/layouts/AppLayout.vue'
import { index as ticketsIndex } from '@/routes/tickets'
import { index as shopIndex } from '@/routes/shop'
import type { BreadcrumbItem } from '@/types'
import type { Ticket } from '@/types/domain'
import { Head, Link, router } from '@inertiajs/vue3'
import { ShoppingCart } from 'lucide-vue-next'

defineProps<{
    ownedTickets: Ticket[]
    managedTickets: Ticket[]
    usableTickets: Ticket[]
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'My Tickets', href: ticketsIndex().url },
]

function formatPrice(cents: number): string {
    return (cents / 100).toFixed(2) + ' €'
}

function statusVariant(status: string): 'default' | 'secondary' | 'destructive' {
    switch (status) {
        case 'Active':
            return 'default'
        case 'CheckedIn':
            return 'secondary'
        case 'Cancelled':
            return 'destructive'
        default:
            return 'secondary'
    }
}
</script>

<template>
    <Head title="My Tickets" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">My Tickets</h1>
                    <p class="text-sm text-muted-foreground">Manage your owned, managed, and assigned tickets</p>
                </div>
                <Button as-child>
                    <Link :href="shopIndex().url">
                        <ShoppingCart class="size-4" />
                        Buy Tickets
                    </Link>
                </Button>
            </div>

            <!-- Owned Tickets -->
            <div class="space-y-3">
                <h2 class="text-lg font-semibold">Owned Tickets</h2>
                <div v-if="ownedTickets.length > 0" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <Card
                        v-for="ticket in ownedTickets"
                        :key="ticket.id"
                        class="cursor-pointer transition-colors hover:bg-muted/50"
                        @click="router.visit(TicketController.show(ticket.id).url)"
                    >
                        <CardHeader class="pb-2">
                            <div class="flex items-center justify-between">
                                <CardTitle class="text-base">{{ ticket.ticket_type?.name }}</CardTitle>
                                <Badge :variant="statusVariant(ticket.status)">{{ ticket.status }}</Badge>
                            </div>
                            <CardDescription>{{ ticket.event?.name }}</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="text-sm text-muted-foreground space-y-1">
                                <p v-if="ticket.manager">Manager: {{ ticket.manager.name }}</p>
                                <p v-if="ticket.ticket_user">User: {{ ticket.ticket_user.name }}</p>
                            </div>
                        </CardContent>
                    </Card>
                </div>
                <p v-else class="text-sm text-muted-foreground">You don't own any tickets yet.</p>
            </div>

            <!-- Managed Tickets -->
            <div class="space-y-3">
                <h2 class="text-lg font-semibold">Managed Tickets</h2>
                <div v-if="managedTickets.length > 0" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <Card
                        v-for="ticket in managedTickets"
                        :key="ticket.id"
                        class="cursor-pointer transition-colors hover:bg-muted/50"
                        @click="router.visit(TicketController.show(ticket.id).url)"
                    >
                        <CardHeader class="pb-2">
                            <div class="flex items-center justify-between">
                                <CardTitle class="text-base">{{ ticket.ticket_type?.name }}</CardTitle>
                                <Badge :variant="statusVariant(ticket.status)">{{ ticket.status }}</Badge>
                            </div>
                            <CardDescription>{{ ticket.event?.name }}</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="text-sm text-muted-foreground space-y-1">
                                <p>Owner: {{ ticket.owner?.name }}</p>
                                <p v-if="ticket.ticket_user">User: {{ ticket.ticket_user.name }}</p>
                            </div>
                        </CardContent>
                    </Card>
                </div>
                <p v-else class="text-sm text-muted-foreground">You don't manage any tickets.</p>
            </div>

            <!-- Usable Tickets -->
            <div class="space-y-3">
                <h2 class="text-lg font-semibold">Assigned Tickets</h2>
                <div v-if="usableTickets.length > 0" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <Card
                        v-for="ticket in usableTickets"
                        :key="ticket.id"
                        class="cursor-pointer transition-colors hover:bg-muted/50"
                        @click="router.visit(TicketController.show(ticket.id).url)"
                    >
                        <CardHeader class="pb-2">
                            <div class="flex items-center justify-between">
                                <CardTitle class="text-base">{{ ticket.ticket_type?.name }}</CardTitle>
                                <Badge :variant="statusVariant(ticket.status)">{{ ticket.status }}</Badge>
                            </div>
                            <CardDescription>{{ ticket.event?.name }}</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="text-sm text-muted-foreground space-y-1">
                                <p>Owner: {{ ticket.owner?.name }}</p>
                            </div>
                        </CardContent>
                    </Card>
                </div>
                <p v-else class="text-sm text-muted-foreground">No tickets assigned to you.</p>
            </div>
        </div>
    </AppLayout>
</template>
