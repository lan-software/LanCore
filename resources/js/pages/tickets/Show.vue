<script setup lang="ts">
import TicketController from '@/actions/App/Domain/Ticketing/Http/Controllers/TicketController'
import Heading from '@/components/Heading.vue'
import InputError from '@/components/InputError.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import AppLayout from '@/layouts/AppLayout.vue'
import { index as ticketsIndex } from '@/routes/tickets'
import type { BreadcrumbItem } from '@/types'
import type { Ticket } from '@/types/domain'
import { Form, Head, Link } from '@inertiajs/vue3'

const props = defineProps<{
    ticket: Ticket
    canUpdateManager: boolean
    canUpdateUser: boolean
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'My Tickets', href: ticketsIndex().url },
    { title: props.ticket.ticket_type?.name ?? `Ticket #${props.ticket.id}`, href: TicketController.show(props.ticket.id).url },
]

function formatPrice(cents: number): string {
    return (cents / 100).toFixed(2) + ' €'
}

function formatDate(dateString: string): string {
    return new Date(dateString).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    })
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
    <Head :title="ticket.ticket_type?.name ?? `Ticket #${ticket.id}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4 max-w-3xl">
            <div>
                <Link :href="ticketsIndex().url" class="text-sm text-muted-foreground hover:text-foreground">
                    &larr; Back to My Tickets
                </Link>
            </div>

            <!-- Ticket Overview -->
            <div class="flex items-center gap-4">
                <h1 class="text-2xl font-bold">{{ ticket.ticket_type?.name }}</h1>
                <Badge :variant="statusVariant(ticket.status)">{{ ticket.status }}</Badge>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">Event</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="font-medium">{{ ticket.event?.name }}</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">Ticket Type</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="font-medium">{{ ticket.ticket_type?.name }}</p>
                        <p v-if="ticket.ticket_type" class="text-sm text-muted-foreground">
                            {{ formatPrice(ticket.ticket_type.price) }}
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">Owner</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="font-medium">{{ ticket.owner?.name }}</p>
                        <p class="text-sm text-muted-foreground">{{ ticket.owner?.email }}</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">Current Assignments</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-1 text-sm">
                            <p>Manager: {{ ticket.manager?.name ?? '—' }}</p>
                            <p>User: {{ ticket.ticket_user?.name ?? '—' }}</p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Addons -->
            <div v-if="ticket.addons && ticket.addons.length > 0" class="space-y-2">
                <h2 class="text-lg font-semibold">Addons</h2>
                <div class="flex flex-wrap gap-2">
                    <Badge v-for="addon in ticket.addons" :key="addon.id" variant="outline">
                        {{ addon.name }}
                    </Badge>
                </div>
            </div>

            <!-- Update Manager -->
            <div v-if="canUpdateManager" class="space-y-4">
                <Heading variant="small" title="Update Manager" description="Assign a user to manage this ticket (can change seat and user)" />
                <Form
                    v-bind="TicketController.updateManager.form(ticket.id)"
                    class="flex items-end gap-4"
                    v-slot="{ errors, processing, recentlySuccessful }"
                >
                    <div class="grid gap-2 flex-1">
                        <Label for="manager_email">Manager Email</Label>
                        <Input
                            id="manager_email"
                            name="manager_email"
                            type="email"
                            :default-value="ticket.manager?.email ?? ''"
                            placeholder="user@example.com"
                        />
                        <InputError :message="errors.manager_email" />
                    </div>
                    <Button type="submit" :disabled="processing">
                        {{ processing ? 'Saving…' : 'Update Manager' }}
                    </Button>
                    <p v-if="recentlySuccessful" class="text-sm text-muted-foreground">Saved.</p>
                </Form>
            </div>

            <!-- Update User -->
            <div v-if="canUpdateUser" class="space-y-4">
                <Heading variant="small" title="Update User" description="Assign a user to use this ticket for entrance" />
                <Form
                    v-bind="TicketController.updateUser.form(ticket.id)"
                    class="flex items-end gap-4"
                    v-slot="{ errors, processing, recentlySuccessful }"
                >
                    <div class="grid gap-2 flex-1">
                        <Label for="user_email">User Email</Label>
                        <Input
                            id="user_email"
                            name="user_email"
                            type="email"
                            :default-value="ticket.ticket_user?.email ?? ''"
                            placeholder="user@example.com"
                        />
                        <InputError :message="errors.user_email" />
                    </div>
                    <Button type="submit" :disabled="processing">
                        {{ processing ? 'Saving…' : 'Update User' }}
                    </Button>
                    <p v-if="recentlySuccessful" class="text-sm text-muted-foreground">Saved.</p>
                </Form>
            </div>


        </div>
    </AppLayout>
</template>
