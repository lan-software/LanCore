<script setup lang="ts">
import { Form, router } from '@inertiajs/vue3';
import { Armchair, Eye, EyeOff, X } from 'lucide-vue-next';
import { ref } from 'vue';
import TicketController from '@/actions/App/Domain/Ticketing/Http/Controllers/TicketController';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { Ticket } from '@/types/domain';

const props = defineProps<{
    ticket: Ticket;
    canUpdateManager?: boolean;
    canUpdateUser?: boolean;
}>();

const showValidationId = ref(false);

function formatPrice(cents: number): string {
    return (cents / 100).toFixed(2) + ' €';
}

function formatDate(dateString: string): string {
    return new Date(dateString).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

function statusVariant(
    status: string,
): 'default' | 'secondary' | 'destructive' {
    switch (status) {
        case 'Active':
            return 'default';
        case 'CheckedIn':
            return 'secondary';
        case 'Cancelled':
            return 'destructive';
        default:
            return 'secondary';
    }
}

function maskedValidationId(id: string): string {
    return '•'.repeat(id.length);
}

const bannerUrl = props.ticket.event?.banner_image_urls?.[0] ?? null;
</script>

<template>
    <div
        class="group relative overflow-hidden rounded-xl border bg-card text-card-foreground shadow-sm"
    >
        <!-- Event Banner with gradient fade -->
        <div v-if="bannerUrl" class="relative h-32 w-full overflow-hidden">
            <img
                :src="bannerUrl"
                :alt="ticket.event?.name ?? 'Event banner'"
                class="h-full w-full object-cover"
            />
            <div
                class="absolute inset-0 bg-gradient-to-b from-transparent via-transparent to-card"
            />
        </div>
        <div v-else class="h-4" />

        <div
            class="space-y-4 p-4"
            :class="{ 'relative z-10 -mt-6': bannerUrl }"
        >
            <!-- Header: Name + Status -->
            <div class="flex items-start justify-between gap-2">
                <div class="min-w-0 flex-1">
                    <h3 class="truncate text-lg font-semibold">
                        {{ ticket.ticket_type?.name }}
                    </h3>
                    <p class="text-sm text-muted-foreground">
                        {{ ticket.event?.name }}
                    </p>
                </div>
                <Badge
                    :variant="statusVariant(ticket.status)"
                    class="shrink-0"
                    >{{ ticket.status }}</Badge
                >
            </div>

            <!-- Event Dates -->
            <div
                v-if="ticket.event?.start_date || ticket.event?.end_date"
                class="flex gap-2 text-sm text-muted-foreground"
            >
                <span v-if="ticket.event?.start_date">{{
                    formatDate(ticket.event.start_date)
                }}</span>
                <span v-if="ticket.event?.start_date && ticket.event?.end_date"
                    >–</span
                >
                <span v-if="ticket.event?.end_date">{{
                    formatDate(ticket.event.end_date)
                }}</span>
            </div>

            <!-- Price -->
            <div v-if="ticket.ticket_type" class="text-sm">
                <span class="text-muted-foreground">Price paid: </span>
                <span class="font-medium">{{
                    formatPrice(ticket.ticket_type.price)
                }}</span>
            </div>

            <!-- Addons -->
            <div
                v-if="ticket.addons && ticket.addons.length > 0"
                class="space-y-1"
            >
                <p
                    class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                >
                    Add-ons
                </p>
                <div class="flex flex-wrap gap-1.5">
                    <Badge
                        v-for="addon in ticket.addons"
                        :key="addon.id"
                        variant="outline"
                        class="text-xs"
                    >
                        {{ addon.name }}
                    </Badge>
                </div>
            </div>

            <!-- Validation ID (Preshared Secret) -->
            <div class="space-y-1">
                <p
                    class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                >
                    Validation ID
                </p>
                <div class="flex items-center gap-2">
                    <code
                        class="rounded bg-muted px-2 py-1 font-mono text-sm tracking-widest"
                    >
                        {{
                            showValidationId
                                ? ticket.validation_id
                                : maskedValidationId(ticket.validation_id)
                        }}
                    </code>
                    <button
                        type="button"
                        class="rounded-md p-1 text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                        @click="showValidationId = !showValidationId"
                    >
                        <Eye v-if="!showValidationId" class="size-4" />
                        <EyeOff v-else class="size-4" />
                        <span class="sr-only"
                            >{{ showValidationId ? 'Hide' : 'Show' }} validation
                            ID</span
                        >
                    </button>
                </div>
            </div>

            <!-- Manager Assignment -->
            <div class="space-y-1">
                <p
                    class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                >
                    Manager
                </p>
                <div v-if="canUpdateManager">
                    <Form
                        v-bind="TicketController.updateManager.form(ticket.id)"
                        class="flex items-center gap-2"
                        v-slot="{ errors, processing, recentlySuccessful }"
                    >
                        <Input
                            name="manager_email"
                            type="email"
                            :default-value="ticket.manager?.email ?? ''"
                            placeholder="Manager email"
                            class="h-8 text-sm"
                        />
                        <Button
                            type="submit"
                            size="sm"
                            variant="outline"
                            :disabled="processing"
                            class="shrink-0"
                        >
                            {{ processing ? '…' : 'Set' }}
                        </Button>
                        <p
                            v-if="recentlySuccessful"
                            class="text-xs text-muted-foreground"
                        >
                            Saved
                        </p>
                        <InputError :message="errors.manager_email" />
                    </Form>
                </div>
                <p v-else class="text-sm">{{ ticket.manager?.name ?? '—' }}</p>
            </div>

            <!-- Ticket Users -->
            <div class="space-y-1">
                <div class="flex items-center gap-2">
                    <p
                        class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                    >
                        Assigned Users
                    </p>
                    <Badge
                        v-if="
                            ticket.ticket_type &&
                            ticket.ticket_type.max_users_per_ticket > 1
                        "
                        variant="outline"
                        class="text-xs"
                    >
                        Group ({{ ticket.users?.length ?? 0 }}/{{
                            ticket.ticket_type.max_users_per_ticket
                        }})
                    </Badge>
                </div>

                <!-- List of assigned users -->
                <div
                    v-if="ticket.users && ticket.users.length > 0"
                    class="space-y-1"
                >
                    <div
                        v-for="user in ticket.users"
                        :key="user.id"
                        class="flex items-center justify-between rounded-md bg-muted px-2 py-1 text-sm"
                    >
                        <span>{{ user.name }} ({{ user.email }})</span>
                        <div class="flex items-center gap-1">
                            <Badge
                                v-if="user.pivot?.checked_in_at"
                                variant="secondary"
                                class="text-xs"
                            >
                                Checked in
                            </Badge>
                            <button
                                v-if="
                                    canUpdateUser && !user.pivot?.checked_in_at
                                "
                                type="button"
                                class="rounded p-0.5 text-muted-foreground transition-colors hover:bg-destructive/10 hover:text-destructive"
                                @click="
                                    router.delete(
                                        TicketController.removeUser({
                                            ticket: ticket.id,
                                            user: user.id,
                                        }).url,
                                    )
                                "
                            >
                                <X class="size-3.5" />
                                <span class="sr-only"
                                    >Remove {{ user.name }}</span
                                >
                            </button>
                        </div>
                    </div>
                </div>
                <p v-else class="text-sm text-muted-foreground">
                    No users assigned
                </p>

                <!-- Add user form (only when there's room for more users) -->
                <div
                    v-if="
                        canUpdateUser &&
                        ticket.ticket_type &&
                        (ticket.users?.length ?? 0) <
                            ticket.ticket_type.max_users_per_ticket
                    "
                >
                    <Form
                        v-bind="TicketController.addUser.form(ticket.id)"
                        class="mt-2 flex items-center gap-2"
                        v-slot="{ errors, processing, recentlySuccessful }"
                    >
                        <Input
                            name="user_email"
                            type="email"
                            placeholder="Add user by email"
                            class="h-8 text-sm"
                        />
                        <Button
                            type="submit"
                            size="sm"
                            variant="outline"
                            :disabled="processing"
                            class="shrink-0"
                        >
                            {{ processing ? '…' : 'Add' }}
                        </Button>
                        <p
                            v-if="recentlySuccessful"
                            class="text-xs text-muted-foreground"
                        >
                            Added
                        </p>
                        <InputError :message="errors.user_email" />
                    </Form>
                </div>
            </div>

            <!-- Seat (placeholder for future implementation) -->
            <div class="space-y-1">
                <p
                    class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                >
                    Seat
                </p>
                <Button variant="outline" size="sm" disabled class="gap-1.5">
                    <Armchair class="size-4" />
                    Pick your Seat
                </Button>
            </div>
        </div>
    </div>
</template>
