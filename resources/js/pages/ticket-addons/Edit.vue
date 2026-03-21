<script setup lang="ts">
import AddonController from '@/actions/App/Domain/Ticketing/Http/Controllers/AddonController'
import Heading from '@/components/Heading.vue'
import InputError from '@/components/InputError.vue'
import { Button } from '@/components/ui/button'
import { Checkbox } from '@/components/ui/checkbox'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Textarea } from '@/components/ui/textarea'
import AppLayout from '@/layouts/AppLayout.vue'
import { index as ticketAddonsIndex } from '@/routes/ticket-addons'
import type { BreadcrumbItem } from '@/types'
import type { TicketAddon } from '@/types/domain'
import { Form, Head, Link, router } from '@inertiajs/vue3'
import { Trash2 } from 'lucide-vue-next'
import { ref } from 'vue'

const props = defineProps<{
    ticketAddon: TicketAddon
    events: { id: number; name: string }[]
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: ticketAddonsIndex().url },
    { title: 'Ticket Addons', href: ticketAddonsIndex().url },
    { title: props.ticketAddon.name, href: AddonController.edit(props.ticketAddon.id).url },
]

const showDeleteDialog = ref(false)

function executeDelete() {
    router.delete(AddonController.destroy(props.ticketAddon.id).url, {
        onSuccess: () => {
            showDeleteDialog.value = false
        },
    })
}
</script>

<template>
    <Head :title="`Edit ${ticketAddon.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-8 p-4 max-w-2xl">
            <div>
                <Link :href="ticketAddonsIndex().url" class="text-sm text-muted-foreground hover:text-foreground">
                    &larr; Back to Ticket Addons
                </Link>
            </div>

            <Form
                v-bind="AddonController.update.form(ticketAddon.id)"
                class="space-y-8"
                v-slot="{ errors, processing, recentlySuccessful }"
            >
                <div class="space-y-4">
                    <Heading variant="small" title="Addon Information" description="Update addon details" />

                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input id="name" name="name" :default-value="ticketAddon.name" required placeholder="Addon name" />
                        <InputError :message="errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="description">Description</Label>
                        <Textarea id="description" name="description" :default-value="ticketAddon.description ?? ''" rows="3" placeholder="Describe the addon…" />
                        <InputError :message="errors.description" />
                    </div>
                </div>

                <div class="space-y-4">
                    <Heading variant="small" title="Pricing & Availability" description="Update price and quota" />

                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="price">Price (cents)</Label>
                            <Input id="price" name="price" type="number" min="0" :default-value="String(ticketAddon.price)" required />
                            <InputError :message="errors.price" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="quota">Quota</Label>
                            <Input id="quota" name="quota" type="number" min="0" :default-value="ticketAddon.quota !== null ? String(ticketAddon.quota) : ''" placeholder="Leave empty for unlimited" />
                            <InputError :message="errors.quota" />
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <Heading variant="small" title="Configuration" description="Update addon behavior" />

                    <div class="grid gap-2">
                        <Label for="seats_consumed">Seats Consumed</Label>
                        <Input id="seats_consumed" name="seats_consumed" type="number" min="0" :default-value="String(ticketAddon.seats_consumed)" />
                        <InputError :message="errors.seats_consumed" />
                    </div>

                    <div class="flex items-center gap-2">
                        <Checkbox id="requires_ticket" name="requires_ticket" :default-value="ticketAddon.requires_ticket" />
                        <Label for="requires_ticket" class="cursor-pointer">Requires a ticket</Label>
                    </div>
                    <InputError :message="errors.requires_ticket" />

                    <div class="flex items-center gap-2">
                        <Checkbox id="is_hidden" name="is_hidden" :default-value="ticketAddon.is_hidden" />
                        <Label for="is_hidden" class="cursor-pointer">Hidden from shop</Label>
                    </div>
                    <InputError :message="errors.is_hidden" />
                </div>

                <div class="space-y-4">
                    <Heading variant="small" title="Association" description="Link this addon to an event" />

                    <div class="grid gap-2">
                        <Label for="event_id">Event</Label>
                        <Select name="event_id" :default-value="String(ticketAddon.event_id)">
                            <SelectTrigger>
                                <SelectValue placeholder="Select an event" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="event in events" :key="event.id" :value="String(event.id)">
                                    {{ event.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="errors.event_id" />
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <Button type="submit" :disabled="processing">
                        {{ processing ? 'Saving…' : 'Save Changes' }}
                    </Button>
                    <p v-if="recentlySuccessful" class="text-sm text-muted-foreground">Saved.</p>
                </div>
            </Form>

            <div class="border-t pt-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-medium text-destructive">Delete Addon</h3>
                        <p class="text-sm text-muted-foreground">Permanently delete this addon.</p>
                    </div>
                    <Button variant="destructive" size="sm" @click="showDeleteDialog = true">
                        <Trash2 class="size-4" />
                        Delete
                    </Button>
                </div>
            </div>
        </div>

        <Dialog v-model:open="showDeleteDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Delete {{ ticketAddon.name }}?</DialogTitle>
                    <DialogDescription>This action cannot be undone.</DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="showDeleteDialog = false">Cancel</Button>
                    <Button variant="destructive" @click="executeDelete">Delete</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
