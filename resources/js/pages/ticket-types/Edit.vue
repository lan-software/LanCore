<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';
import TicketTypeController from '@/actions/App/Domain/Ticketing/Http/Controllers/TicketTypeController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as ticketTypesRoute } from '@/routes/ticket-types';
import type { BreadcrumbItem } from '@/types';
import type { TicketType } from '@/types/domain';

const props = defineProps<{
    ticketType: TicketType;
    events: { id: number; name: string }[];
    categories: { id: number; name: string }[];
    groups: { id: number; name: string }[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: ticketTypesRoute().url },
    { title: 'Ticket Types', href: ticketTypesRoute().url },
    {
        title: props.ticketType.name,
        href: TicketTypeController.edit(props.ticketType.id).url,
    },
];

function formatDateTimeLocal(dateString: string | null): string {
    if (!dateString) {
        return '';
    }

    const date = new Date(dateString);
    const pad = (n: number) => String(n).padStart(2, '0');

    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
}

const showDeleteDialog = ref(false);

function executeDelete() {
    router.delete(TicketTypeController.destroy(props.ticketType.id).url, {
        onSuccess: () => {
            showDeleteDialog.value = false;
        },
    });
}
</script>

<template>
    <Head :title="`Edit ${ticketType.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full max-w-2xl flex-1 flex-col gap-8 p-4">
            <!-- Back link -->
            <div>
                <Link
                    :href="ticketTypesRoute().url"
                    class="text-sm text-muted-foreground hover:text-foreground"
                >
                    &larr; Back to Ticket Types
                </Link>
            </div>

            <!-- Locked alert -->
            <div v-if="ticketType.is_locked" class="flex items-center gap-2">
                <Badge variant="default">Locked</Badge>
                <span class="text-sm text-muted-foreground">
                    This ticket type is locked. Some fields cannot be modified.
                </span>
            </div>

            <Form
                v-bind="TicketTypeController.update.form(ticketType.id)"
                class="space-y-8"
                v-slot="{ errors, processing, recentlySuccessful }"
            >
                <!-- Ticket Type Information -->
                <div class="space-y-4">
                    <Heading
                        variant="small"
                        title="Ticket Type Information"
                        description="Update the basic details for this ticket type"
                    />

                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input
                            id="name"
                            name="name"
                            :default-value="ticketType.name"
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
                            :default-value="ticketType.description ?? ''"
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
                                :default-value="String(ticketType.price)"
                                :disabled="ticketType.is_locked"
                                required
                                min="0"
                            />
                            <InputError :message="errors.price" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="quota">Quota</Label>
                            <Input
                                id="quota"
                                type="number"
                                name="quota"
                                :default-value="String(ticketType.quota)"
                                :disabled="ticketType.is_locked"
                                required
                                min="0"
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
                            :default-value="
                                ticketType.max_per_user !== null
                                    ? String(ticketType.max_per_user)
                                    : ''
                            "
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
                        <Label for="seats_per_user">Seats per User</Label>
                        <Input
                            id="seats_per_user"
                            type="number"
                            name="seats_per_user"
                            :default-value="String(ticketType.seats_per_user)"
                            :disabled="ticketType.is_locked"
                            min="1"
                        />
                        <InputError :message="errors.seats_per_user" />
                    </div>

                    <div class="flex items-center gap-2">
                        <Checkbox
                            id="is_seatable"
                            name="is_seatable"
                            :default-value="ticketType.is_seatable"
                            :disabled="ticketType.is_locked"
                        />
                        <Label for="is_seatable" class="cursor-pointer"
                            >Seatable</Label
                        >
                    </div>
                </div>

                <!-- Group Ticket -->
                <div class="space-y-4">
                    <Heading
                        variant="small"
                        title="Group Ticket"
                        description="Allow multiple users per ticket. Total seats = seats per user x max users."
                    />

                    <div class="grid gap-2">
                        <Label for="max_users_per_ticket"
                            >Max Users per Ticket</Label
                        >
                        <Input
                            id="max_users_per_ticket"
                            type="number"
                            name="max_users_per_ticket"
                            :default-value="
                                String(ticketType.max_users_per_ticket)
                            "
                            :disabled="ticketType.is_locked"
                            min="1"
                        />
                        <InputError :message="errors.max_users_per_ticket" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="check_in_mode">Check-in Mode</Label>
                        <Select
                            name="check_in_mode"
                            :default-value="ticketType.check_in_mode"
                            :disabled="ticketType.is_locked"
                        >
                            <SelectTrigger>
                                <SelectValue
                                    placeholder="Select check-in mode"
                                />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="individual">
                                    Individual (each user checks in separately)
                                </SelectItem>
                                <SelectItem value="group">
                                    Group (all users check in together)
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="errors.check_in_mode" />
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
                                :default-value="
                                    formatDateTimeLocal(
                                        ticketType.purchase_from,
                                    )
                                "
                            />
                            <InputError :message="errors.purchase_from" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="purchase_until">Purchase Until</Label>
                            <Input
                                id="purchase_until"
                                type="datetime-local"
                                name="purchase_until"
                                :default-value="
                                    formatDateTimeLocal(
                                        ticketType.purchase_until,
                                    )
                                "
                            />
                            <InputError :message="errors.purchase_until" />
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <Checkbox
                            id="is_hidden"
                            name="is_hidden"
                            :default-value="ticketType.is_hidden"
                        />
                        <Label for="is_hidden" class="cursor-pointer"
                            >Hidden</Label
                        >
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
                        <Select
                            name="event_id"
                            :default-value="
                                ticketType.event_id
                                    ? String(ticketType.event_id)
                                    : undefined
                            "
                            :disabled="ticketType.is_locked"
                        >
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
                        <Select
                            name="ticket_category_id"
                            :default-value="
                                ticketType.ticket_category_id
                                    ? String(ticketType.ticket_category_id)
                                    : undefined
                            "
                        >
                            <SelectTrigger>
                                <SelectValue
                                    placeholder="Select a category (optional)"
                                />
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
                        <Select
                            name="ticket_group_id"
                            :default-value="
                                ticketType.ticket_group_id
                                    ? String(ticketType.ticket_group_id)
                                    : undefined
                            "
                        >
                            <SelectTrigger>
                                <SelectValue
                                    placeholder="Select a group (optional)"
                                />
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

                <!-- Actions -->
                <div class="flex items-center gap-4">
                    <Button type="submit" :disabled="processing">
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
                        <h3 class="text-sm font-medium text-destructive">
                            Delete Ticket Type
                        </h3>
                        <p class="text-sm text-muted-foreground">
                            Permanently delete this ticket type.
                        </p>
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
                    <DialogTitle>Delete {{ ticketType.name }}?</DialogTitle>
                    <DialogDescription>
                        This action cannot be undone. The ticket type will be
                        permanently removed.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="showDeleteDialog = false">
                        Cancel
                    </Button>
                    <Button variant="destructive" @click="executeDelete">
                        Delete
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
