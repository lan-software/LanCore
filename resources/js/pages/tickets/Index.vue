<script setup lang="ts">
import TicketCard from '@/components/TicketCard.vue'
import { Button } from '@/components/ui/button'
import AppLayout from '@/layouts/AppLayout.vue'
import { index as ticketsIndex } from '@/routes/tickets'
import { index as shopIndex } from '@/routes/shop'
import type { BreadcrumbItem } from '@/types'
import type { Ticket } from '@/types/domain'
import { Head, Link } from '@inertiajs/vue3'
import { ShoppingCart } from 'lucide-vue-next'

defineProps<{
    ownedTickets: Ticket[]
    managedTickets: Ticket[]
    usableTickets: Ticket[]
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'My Tickets', href: ticketsIndex().url },
]
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
                <div v-if="ownedTickets.length > 0" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <TicketCard
                        v-for="ticket in ownedTickets"
                        :key="ticket.id"
                        :ticket="ticket"
                        :can-update-manager="true"
                        :can-update-user="true"
                    />
                </div>
                <p v-else class="text-sm text-muted-foreground">You don't own any tickets yet.</p>
            </div>

            <!-- Managed Tickets -->
            <div class="space-y-3">
                <h2 class="text-lg font-semibold">Managed Tickets</h2>
                <div v-if="managedTickets.length > 0" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <TicketCard
                        v-for="ticket in managedTickets"
                        :key="ticket.id"
                        :ticket="ticket"
                        :can-update-user="true"
                    />
                </div>
                <p v-else class="text-sm text-muted-foreground">You don't manage any tickets.</p>
            </div>

            <!-- Usable Tickets -->
            <div class="space-y-3">
                <h2 class="text-lg font-semibold">Assigned Tickets</h2>
                <div v-if="usableTickets.length > 0" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <TicketCard
                        v-for="ticket in usableTickets"
                        :key="ticket.id"
                        :ticket="ticket"
                    />
                </div>
                <p v-else class="text-sm text-muted-foreground">No tickets assigned to you.</p>
            </div>
        </div>
    </AppLayout>
</template>
