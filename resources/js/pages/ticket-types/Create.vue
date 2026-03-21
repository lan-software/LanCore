<script setup lang="ts">
import TicketTypeController from '@/actions/App/Domain/Ticketing/Http/Controllers/TicketTypeController'
import Heading from '@/components/Heading.vue'
import InputError from '@/components/InputError.vue'
import { Button } from '@/components/ui/button'
import { Checkbox } from '@/components/ui/checkbox'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Textarea } from '@/components/ui/textarea'
import AppLayout from '@/layouts/AppLayout.vue'
import { index as ticketTypesRoute } from '@/routes/ticket-types'
import type { BreadcrumbItem } from '@/types'
import { Form, Head, Link } from '@inertiajs/vue3'

defineProps<{
    events: { id: number; name: string }[]
    categories: { id: number; name: string }[]
    groups: { id: number; name: string }[]
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: ticketTypesRoute().url },
    { title: 'Ticket Types', href: ticketTypesRoute().url },
    { title: 'Create', href: TicketTypeController.create().url },
]
</script>

<template>
    <Head title="Create Ticket Type" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-8 p-4 max-w-2xl">
            <!-- Back link -->
            <div>
                <Link
                    :href="ticketTypesRoute().url"
                    class="text-sm text-muted-foreground hover:text-foreground"
                >
                    &larr; Back to Ticket Types
                </Link>
            </div>

            <Form
                v-bind="TicketTypeController.store.form()"
                class="space-y-8"
                v-slot="{ errors, processing }"
            >
                <!-- Ticket Type Information -->
                <div class="space-y-4">
                    <Heading
                        variant="small"
                        title="Ticket Type Information"
                        description="Provide the basic details for this ticket type"
                    />

                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input
                            id="name"
                            name="name"
                            required
                            placeholder="Ticket type name"
                        />
                        <InputError :message="errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="description">Description</Label>
                        <Textarea
                            id="description"
                            name="description"
                            rows="4"
                            placeholder="Describe the ticket type…"
                        />
                        <InputError :message="errors.description" />
                    </div>
                </div>

                <!-- Pricing & Quota -->
                <div class="space-y-4">
                    <Heading
                        variant="small"
                        title="Pricing & Quota"
                        description="Set the price (in cents) and available quantity"
                    />

                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="price">Price (cents)</Label>
                            <Input
                                id="price"
                                type="number"
                                name="price"
                                required
                                min="0"
                                placeholder="0"
                            />
                            <InputError :message="errors.price" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="quota">Quota</Label>
                            <Input
                                id="quota"
                                type="number"
                                name="quota"
                                required
                                min="0"
                                placeholder="0"
                            />
                            <InputError :message="errors.quota" />
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label for="max_per_user">Max Tickets per User</Label>
                        <Input
                            id="max_per_user"
                            type="number"
                            name="max_per_user"
                            min="1"
                            placeholder="Leave empty for unlimited"
                        />
                        <InputError :message="errors.max_per_user" />
                    </div>
                </div>

                <!-- Seating -->
                <div class="space-y-4">
                    <Heading
                        variant="small"
                        title="Seating"
                        description="Configure seat allocation for this ticket type"
                    />

                    <div class="grid gap-2">
                        <Label for="seats_per_ticket">Seats per Ticket</Label>
                        <Input
                            id="seats_per_ticket"
                            type="number"
                            name="seats_per_ticket"
                            min="1"
                            default-value="1"
                            placeholder="1"
                        />
                        <InputError :message="errors.seats_per_ticket" />
                    </div>

                    <div class="flex items-center gap-2">
                        <Checkbox
                            id="is_row_ticket"
                            name="is_row_ticket"
                        />
                        <Label for="is_row_ticket" class="cursor-pointer">Row ticket</Label>
                    </div>

                    <div class="flex items-center gap-2">
                        <Checkbox
                            id="is_seatable"
                            name="is_seatable"
                        />
                        <Label for="is_seatable" class="cursor-pointer">Seatable</Label>
                    </div>
                </div>

                <!-- Purchase Window -->
                <div class="space-y-4">
                    <Heading
                        variant="small"
                        title="Purchase Window"
                        description="Optionally restrict when this ticket type can be purchased"
                    />

                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="purchase_from">Purchase From</Label>
                            <Input
                                id="purchase_from"
                                type="datetime-local"
                                name="purchase_from"
                            />
                            <InputError :message="errors.purchase_from" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="purchase_until">Purchase Until</Label>
                            <Input
                                id="purchase_until"
                                type="datetime-local"
                                name="purchase_until"
                            />
                            <InputError :message="errors.purchase_until" />
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <Checkbox
                            id="is_hidden"
                            name="is_hidden"
                        />
                        <Label for="is_hidden" class="cursor-pointer">Hidden</Label>
                    </div>
                </div>

                <!-- Associations -->
                <div class="space-y-4">
                    <Heading
                        variant="small"
                        title="Associations"
                        description="Link this ticket type to an event, category, or group"
                    />

                    <div class="grid gap-2">
                        <Label for="event_id">Event</Label>
                        <Select name="event_id">
                            <SelectTrigger>
                                <SelectValue placeholder="Select an event" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="event in events"
                                    :key="event.id"
                                    :value="String(event.id)"
                                >
                                    {{ event.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="errors.event_id" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="ticket_category_id">Category</Label>
                        <Select name="ticket_category_id">
                            <SelectTrigger>
                                <SelectValue placeholder="Select a category (optional)" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="category in categories"
                                    :key="category.id"
                                    :value="String(category.id)"
                                >
                                    {{ category.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="errors.ticket_category_id" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="ticket_group_id">Group</Label>
                        <Select name="ticket_group_id">
                            <SelectTrigger>
                                <SelectValue placeholder="Select a group (optional)" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="group in groups"
                                    :key="group.id"
                                    :value="String(group.id)"
                                >
                                    {{ group.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="errors.ticket_group_id" />
                    </div>
                </div>

                <!-- Submit -->
                <div class="flex items-center gap-4">
                    <Button
                        type="submit"
                        :disabled="processing"
                    >
                        {{ processing ? 'Creating…' : 'Create Ticket Type' }}
                    </Button>
                </div>
            </Form>
        </div>
    </AppLayout>
</template>
