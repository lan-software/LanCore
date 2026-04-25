<script setup lang="ts">
import { Form, Link, router } from '@inertiajs/vue3';
import { Armchair, Download, QrCode, RefreshCw, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import TicketController from '@/actions/App/Domain/Ticketing/Http/Controllers/TicketController';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { formatCents } from '@/lib/money';
import { picker as seatPickerRoute } from '@/routes/events/seats';
import type { SeatAssignment, Ticket } from '@/types/domain';

const { t } = useI18n();

const props = defineProps<{
    ticket: Ticket;
    canUpdateManager?: boolean;
    canUpdateUser?: boolean;
    canRotateToken?: boolean;
}>();

const showRotateButton = computed<boolean>(
    () =>
        props.canRotateToken === true ||
        (props.ticket as { can_rotate_token?: boolean }).can_rotate_token ===
            true,
);

function confirmRotate(e: Event): void {
    const ok = window.confirm(t('ticketCard.rotateConfirm'));

    if (!ok) {
        e.preventDefault();
    }
}

const showQrCode = ref(false);

function formatPrice(cents: number): string {
    return formatCents(cents);
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

const bannerUrl = props.ticket.event?.banner_image_urls?.[0] ?? null;

const isSeatable = computed<boolean>(
    () =>
        (props.ticket.ticket_type as { is_seatable?: boolean } | undefined)
            ?.is_seatable ?? true,
);

const seatableAssignees = computed<{ id: number; name: string }[]>(() => {
    const users = props.ticket.users ?? [];

    if (users.length > 0) {
        return users.map((u) => ({ id: u.id, name: u.name }));
    }

    if (props.ticket.owner) {
        return [{ id: props.ticket.owner.id, name: props.ticket.owner.name }];
    }

    return [];
});

function seatAssignmentFor(userId: number): SeatAssignment | undefined {
    return props.ticket.seat_assignments?.find((a) => a.user_id === userId);
}

function pickerUrl(userId: number): string {
    if (!props.ticket.event_id) {
        return '#';
    }

    return seatPickerRoute(props.ticket.event_id, {
        query: { ticket: props.ticket.id, user: userId },
    }).url;
}
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
                    v-if="
                        ticket.order?.payment_method === 'on_site' &&
                        ticket.order?.paid_at === null
                    "
                    variant="outline"
                    class="shrink-0 border-amber-500 text-amber-600"
                    >{{ $t('ticketCard.payOnSite') }}</Badge
                >
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
                <span class="text-muted-foreground"
                    >{{ $t('ticketCard.pricePaid') }}
                </span>
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
                    {{ $t('ticketCard.addonsHeading') }}
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

            <!-- QR Code & Download -->
            <div class="flex flex-wrap items-center gap-2">
                <Button
                    variant="outline"
                    size="sm"
                    class="gap-1.5"
                    @click="showQrCode = !showQrCode"
                >
                    <QrCode class="size-4" />
                    {{
                        showQrCode
                            ? $t('ticketCard.hideQrCode')
                            : $t('ticketCard.showQrCode')
                    }}
                </Button>
                <a :href="`/tickets/${ticket.id}/download`">
                    <Button variant="outline" size="sm" class="gap-1.5">
                        <Download class="size-4" />
                        {{ $t('ticketCard.downloadPdf') }}
                    </Button>
                </a>
                <Form
                    v-if="showRotateButton"
                    v-bind="TicketController.rotateTokenUser.form(ticket.id)"
                    @submit="confirmRotate"
                >
                    <Button
                        type="submit"
                        variant="outline"
                        size="sm"
                        class="gap-1.5"
                    >
                        <RefreshCw class="size-4" />
                        {{ $t('ticketCard.rotateQr') }}
                    </Button>
                </Form>
            </div>

            <!-- QR Code Display -->
            <div
                v-if="showQrCode"
                class="flex flex-col items-center gap-3 rounded-lg border bg-white p-6 dark:bg-gray-950"
            >
                <img
                    :src="`/tickets/${ticket.id}/qr`"
                    :alt="$t('ticketCard.qrAlt', { id: ticket.id })"
                    class="size-48"
                />
                <p
                    class="font-mono text-sm font-bold tracking-normal text-foreground sm:tracking-[0.2em]"
                >
                    {{ $t('ticketCard.ticketNumber', { id: ticket.id }) }}
                </p>
                <p class="text-xs text-muted-foreground">
                    {{ $t('ticketCard.scanAtEntrance') }}
                </p>
            </div>

            <!-- Manager Assignment -->
            <div class="space-y-1">
                <p
                    class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                >
                    {{ $t('ticketCard.manager') }}
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
                            :placeholder="$t('ticketCard.managerEmailPlaceholder')"
                            class="h-8 text-sm"
                        />
                        <Button
                            type="submit"
                            size="sm"
                            variant="outline"
                            :disabled="processing"
                            class="shrink-0"
                        >
                            {{ processing ? '…' : $t('ticketCard.set') }}
                        </Button>
                        <p
                            v-if="recentlySuccessful"
                            class="text-xs text-muted-foreground"
                        >
                            {{ $t('ticketCard.saved') }}
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
                        {{ $t('ticketCard.assignedUsers') }}
                    </p>
                    <Badge
                        v-if="
                            ticket.ticket_type &&
                            ticket.ticket_type.max_users_per_ticket > 1
                        "
                        variant="outline"
                        class="text-xs"
                    >
                        {{
                            $t('ticketCard.groupCount', {
                                current: ticket.users?.length ?? 0,
                                max: ticket.ticket_type.max_users_per_ticket,
                            })
                        }}
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
                                {{ $t('ticketCard.checkedIn') }}
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
                                <span class="sr-only">
                                    {{
                                        $t('ticketCard.removeUser', {
                                            name: user.name,
                                        })
                                    }}
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
                <p v-else class="text-sm text-muted-foreground">
                    {{ $t('ticketCard.noUsers') }}
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
                            :placeholder="$t('ticketCard.addUserPlaceholder')"
                            class="h-8 text-sm"
                        />
                        <Button
                            type="submit"
                            size="sm"
                            variant="outline"
                            :disabled="processing"
                            class="shrink-0"
                        >
                            {{ processing ? '…' : $t('ticketCard.addUser') }}
                        </Button>
                        <p
                            v-if="recentlySuccessful"
                            class="text-xs text-muted-foreground"
                        >
                            {{ $t('ticketCard.added') }}
                        </p>
                        <InputError :message="errors.user_email" />
                    </Form>
                </div>
            </div>

            <!-- Seat -->
            <div v-if="isSeatable" class="space-y-1">
                <p
                    class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                >
                    {{
                        seatableAssignees.length > 1
                            ? $t('tickets.seat.seats')
                            : $t('tickets.seat.seat')
                    }}
                </p>
                <div
                    v-if="seatableAssignees.length === 0"
                    class="text-sm text-muted-foreground"
                >
                    {{ $t('tickets.seat.addAttendeeHint') }}
                </div>
                <ul v-else class="space-y-1.5">
                    <li
                        v-for="assignee in seatableAssignees"
                        :key="assignee.id"
                        class="flex flex-wrap items-center justify-between gap-2 rounded-md bg-muted/40 px-2 py-1.5 text-sm"
                    >
                        <span class="truncate">{{ assignee.name }}</span>
                        <span class="flex items-center gap-2">
                            <Badge
                                v-if="seatAssignmentFor(assignee.id)"
                                variant="secondary"
                                class="gap-1 font-mono text-xs"
                            >
                                <Armchair class="size-3" />
                                {{
                                    seatAssignmentFor(assignee.id)
                                        ?.seat_title ??
                                    seatAssignmentFor(assignee.id)?.seat_id
                                }}
                            </Badge>
                            <Link :href="pickerUrl(assignee.id)">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    class="gap-1.5"
                                >
                                    <Armchair class="size-4" />
                                    {{
                                        seatAssignmentFor(assignee.id)
                                            ? $t('tickets.seat.changeSeat')
                                            : $t('tickets.seat.pickSeat')
                                    }}
                                </Button>
                            </Link>
                        </span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>
