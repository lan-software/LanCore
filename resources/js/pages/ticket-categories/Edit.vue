<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3'
import { Trash2 } from 'lucide-vue-next'
import { ref } from 'vue'
import TicketCategoryController from '@/actions/App/Domain/Ticketing/Http/Controllers/TicketCategoryController'
import Heading from '@/components/Heading.vue'
import InputError from '@/components/InputError.vue'
import { Button } from '@/components/ui/button'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Textarea } from '@/components/ui/textarea'
import AppLayout from '@/layouts/AppLayout.vue'
import { index as ticketCategoriesIndex } from '@/routes/ticket-categories'
import type { BreadcrumbItem } from '@/types'
import type { TicketCategory } from '@/types/domain'

const props = defineProps<{
    ticketCategory: TicketCategory
    events: { id: number; name: string }[]
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: ticketCategoriesIndex().url },
    { title: 'Ticket Categories', href: ticketCategoriesIndex().url },
    { title: props.ticketCategory.name, href: TicketCategoryController.edit(props.ticketCategory.id).url },
]

const showDeleteDialog = ref(false)

function executeDelete() {
    router.delete(TicketCategoryController.destroy(props.ticketCategory.id).url, {
        onSuccess: () => {
            showDeleteDialog.value = false
        },
    })
}
</script>

<template>
    <Head :title="`Edit ${ticketCategory.name}`" />

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
                v-bind="TicketCategoryController.update.form(ticketCategory.id)"
                class="space-y-8"
                v-slot="{ errors, processing, recentlySuccessful }"
            >
                <!-- Category Information -->
                <div class="space-y-4">
                    <Heading
                        variant="small"
                        title="Category Information"
                        description="Update the basic details for this ticket category"
                    />

                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input
                            id="name"
                            name="name"
                            :default-value="ticketCategory.name"
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
                            :default-value="ticketCategory.description ?? ''"
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
                            :default-value="String(ticketCategory.sort_order)"
                            min="0"
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
                        <Select
                            name="event_id"
                            :default-value="ticketCategory.event_id ? String(ticketCategory.event_id) : undefined"
                        >
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
                        {{ processing ? 'Saving…' : 'Save Changes' }}
                    </Button>

                    <p
                        v-if="recentlySuccessful"
                        class="text-sm text-muted-foreground"
                    >
                        Saved.
                    </p>
                </div>
            </Form>

            <!-- Delete section -->
            <div class="border-t pt-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-medium text-destructive">Delete Ticket Category</h3>
                        <p class="text-sm text-muted-foreground">Permanently delete this ticket category.</p>
                    </div>
                    <Button
                        variant="destructive"
                        size="sm"
                        @click="showDeleteDialog = true"
                    >
                        <Trash2 class="size-4" />
                        Delete
                    </Button>
                </div>
            </div>
        </div>

        <!-- Delete confirmation dialog -->
        <Dialog v-model:open="showDeleteDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Delete {{ ticketCategory.name }}?</DialogTitle>
                    <DialogDescription>
                        This action cannot be undone. The ticket category will be permanently removed.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button
                        variant="outline"
                        @click="showDeleteDialog = false"
                    >
                        Cancel
                    </Button>
                    <Button
                        variant="destructive"
                        @click="executeDelete"
                    >
                        Delete
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
