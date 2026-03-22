<script setup lang="ts">
import TicketCategoryController from '@/actions/App/Domain/Ticketing/Http/Controllers/TicketCategoryController'
import Heading from '@/components/Heading.vue'
import InputError from '@/components/InputError.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Textarea } from '@/components/ui/textarea'
import AppLayout from '@/layouts/AppLayout.vue'
import { index as ticketCategoriesIndex } from '@/routes/ticket-categories'
import type { BreadcrumbItem } from '@/types'
import { Form, Head, Link } from '@inertiajs/vue3'

defineProps<{
    events: { id: number; name: string }[]
    selectedEventId?: number | null
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: ticketCategoriesIndex().url },
    { title: 'Ticket Categories', href: ticketCategoriesIndex().url },
    { title: 'Create', href: TicketCategoryController.create().url },
]
</script>

<template>
    <Head title="Create Ticket Category" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-8 p-4 max-w-2xl">
            <!-- Back link -->
            <div>
                <Link
                    :href="ticketCategoriesIndex().url"
                    class="text-sm text-muted-foreground hover:text-foreground"
                >
                    &larr; Back to Ticket Categories
                </Link>
            </div>

            <Form
                v-bind="TicketCategoryController.store.form()"
                class="space-y-8"
                v-slot="{ errors, processing }"
            >
                <!-- Category Information -->
                <div class="space-y-4">
                    <Heading
                        variant="small"
                        title="Category Information"
                        description="Provide the basic details for this ticket category"
                    />

                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input
                            id="name"
                            name="name"
                            required
                            placeholder="Category name"
                        />
                        <InputError :message="errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="description">Description</Label>
                        <Textarea
                            id="description"
                            name="description"
                            rows="4"
                            placeholder="Describe the ticket category…"
                        />
                        <InputError :message="errors.description" />
                    </div>
                </div>

                <!-- Display -->
                <div class="space-y-4">
                    <Heading
                        variant="small"
                        title="Display"
                        description="Configure how this category appears in listings"
                    />

                    <div class="grid gap-2">
                        <Label for="sort_order">Sort Order</Label>
                        <Input
                            id="sort_order"
                            type="number"
                            name="sort_order"
                            min="0"
                            default-value="0"
                            placeholder="0"
                        />
                        <InputError :message="errors.sort_order" />
                    </div>
                </div>

                <!-- Association -->
                <div class="space-y-4">
                    <Heading
                        variant="small"
                        title="Association"
                        description="Optionally link this category to an event"
                    />

                    <div class="grid gap-2">
                        <Label for="event_id">Event</Label>
                        <Select name="event_id" :default-value="selectedEventId ? String(selectedEventId) : undefined">
                            <SelectTrigger>
                                <SelectValue placeholder="Select an event (optional)" />
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
                </div>

                <!-- Actions -->
                <div class="flex items-center gap-4">
                    <Button
                        type="submit"
                        :disabled="processing"
                    >
                        {{ processing ? 'Creating…' : 'Create Category' }}
                    </Button>
                </div>
            </Form>
        </div>
    </AppLayout>
</template>
