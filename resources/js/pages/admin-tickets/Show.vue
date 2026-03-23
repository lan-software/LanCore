<script setup lang="ts">
import { show as adminTicketShow } from '@/actions/App/Domain/Ticketing/Http/Controllers/AdminTicketController'
import OrderController from '@/actions/App/Domain/Shop/Http/Controllers/OrderController'
import { edit as eventEdit } from '@/actions/App/Domain/Event/Http/Controllers/EventController'
import { edit as ticketTypeEdit } from '@/actions/App/Domain/Ticketing/Http/Controllers/TicketTypeController'
import { show as userShow } from '@/actions/App/Http/Controllers/Users/UserController'
import Heading from '@/components/Heading.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import AppLayout from '@/layouts/AppLayout.vue'
import { index as adminTicketsIndex } from '@/routes/admin-tickets'
import type { BreadcrumbItem } from '@/types'
import type { Ticket } from '@/types/domain'
import { Head, Link } from '@inertiajs/vue3'

const props = defineProps<{
    ticket: Ticket
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: adminTicketsIndex().url },
    { title: 'Tickets', href: adminTicketsIndex().url },
    { title: props.ticket.ticket_type?.name ?? `Ticket #${props.ticket.id}`, href: adminTicketShow(props.ticket.id).url },
]

function formatDate(dateString: string): string {
    return new Date(dateString).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    })
}

const statusVariant: Record<string, 'default' | 'secondary' | 'destructive' | 'outline'> = {
    Active: 'default',
    CheckedIn: 'secondary',
    Cancelled: 'destructive',
}
</script>

<template>
    <Head :title="ticket.ticket_type?.name ?? `Ticket #${ticket.id}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4 max-w-4xl">
            <div>
                <Link :href="adminTicketsIndex().url" class="text-sm text-muted-foreground hover:text-foreground">
                    &larr; Back to Tickets
                </Link>
            </div>

            <Heading
                :title="ticket.ticket_type?.name ?? `Ticket #${ticket.id}`"
                :description="`Validation ID: ${ticket.validation_id}`"
            />

            <!-- Ticket Details -->
            <Card>
                <CardHeader>
                    <CardTitle>Ticket Details</CardTitle>
                </CardHeader>
                <CardContent>
                    <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <dt class="text-sm font-medium text-muted-foreground">Status</dt>
                            <dd class="mt-1">
                                <Badge :variant="statusVariant[ticket.status] ?? 'outline'">{{ ticket.status }}</Badge>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-muted-foreground">Validation ID</dt>
                            <dd class="mt-1 text-sm font-mono">{{ ticket.validation_id }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-muted-foreground">Created</dt>
                            <dd class="mt-1 text-sm">{{ formatDate(ticket.created_at) }}</dd>
                        </div>
                        <div v-if="ticket.checked_in_at">
                            <dt class="text-sm font-medium text-muted-foreground">Checked In At</dt>
                            <dd class="mt-1 text-sm">{{ formatDate(ticket.checked_in_at) }}</dd>
                        </div>
                    </dl>
                </CardContent>
            </Card>

            <!-- Ticket Type -->
            <Card v-if="ticket.ticket_type">
                <CardHeader>
                    <CardTitle>Ticket Type</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-medium">{{ ticket.ticket_type.name }}</p>
                            <p v-if="ticket.ticket_type.ticket_category" class="text-sm text-muted-foreground">
                                Category: {{ ticket.ticket_type.ticket_category.name }}
                            </p>
                        </div>
                        <Button variant="outline" size="sm" as-child>
                            <Link :href="ticketTypeEdit(ticket.ticket_type_id).url">View Type</Link>
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Event -->
            <Card v-if="ticket.event">
                <CardHeader>
                    <CardTitle>Event</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="flex items-center justify-between">
                        <p class="font-medium">{{ ticket.event.name }}</p>
                        <Button variant="outline" size="sm" as-child>
                            <Link :href="eventEdit(ticket.event_id).url">View Event</Link>
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Order -->
            <Card v-if="ticket.order">
                <CardHeader>
                    <CardTitle>Order</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-medium">Order #{{ ticket.order.id }}</p>
                            <p class="text-sm text-muted-foreground">
                                {{ ticket.order.user?.name }} &middot; {{ ticket.order.status }}
                            </p>
                        </div>
                        <Button variant="outline" size="sm" as-child>
                            <Link :href="OrderController.show(ticket.order_id).url">View Order</Link>
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Owner -->
            <Card v-if="ticket.owner">
                <CardHeader>
                    <CardTitle>Owner</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-medium">{{ ticket.owner.name }}</p>
                            <p class="text-sm text-muted-foreground">{{ ticket.owner.email }}</p>
                        </div>
                        <Button variant="outline" size="sm" as-child>
                            <Link :href="userShow(ticket.owner_id).url">View User</Link>
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Manager -->
            <Card v-if="ticket.manager">
                <CardHeader>
                    <CardTitle>Manager</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-medium">{{ ticket.manager.name }}</p>
                            <p class="text-sm text-muted-foreground">{{ ticket.manager.email }}</p>
                        </div>
                        <Button variant="outline" size="sm" as-child>
                            <Link :href="userShow(ticket.manager.id).url">View User</Link>
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Assigned User -->
            <Card v-if="ticket.ticket_user">
                <CardHeader>
                    <CardTitle>Assigned User</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-medium">{{ ticket.ticket_user.name }}</p>
                            <p class="text-sm text-muted-foreground">{{ ticket.ticket_user.email }}</p>
                        </div>
                        <Button variant="outline" size="sm" as-child>
                            <Link :href="userShow(ticket.ticket_user.id).url">View User</Link>
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Addons -->
            <Card v-if="ticket.addons && ticket.addons.length > 0">
                <CardHeader>
                    <CardTitle>Addons</CardTitle>
                </CardHeader>
                <CardContent>
                    <ul class="space-y-2">
                        <li v-for="addon in ticket.addons" :key="addon.id" class="flex items-center justify-between">
                            <span class="font-medium">{{ addon.name }}</span>
                            <span class="text-sm text-muted-foreground">{{ (addon.price / 100).toFixed(2) }} €</span>
                        </li>
                    </ul>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
