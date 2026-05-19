<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Armchair, ChevronLeft, Maximize, Target } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import {
    destroy as releaseAction,
    store as assignAction,
} from '@/actions/App/Domain/Seating/Http/Controllers/SeatPickerController';
import Heading from '@/components/Heading.vue';
import SeatPlanViewer from '@/components/seat-plan/SeatPlanViewer.vue';
import {
    notifyReady,
    useSeatPlanViewer,
} from '@/components/seat-plan/useSeatPlanViewer';
import SeatedUserHoverCard from '@/components/seating/SeatedUserHoverCard.vue';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { getInitials } from '@/composables/useInitials';
import AppLayout from '@/layouts/AppLayout.vue';
import { picker as pickerRoute } from '@/routes/events/seats';
import { show as profileShow } from '@/routes/public-profile';
import type { BreadcrumbItem } from '@/types';
import type { SeatPlanBlock, SeatPlanData, SeatPlanSeat } from '@/types/domain';

interface SeatPlan {
    id: string;
    name: string;
    background_image_url?: string | null;
    labels?: { id?: string; title: string; x: number; y: number }[];
    blocks: SeatPlanBlock[];
}

interface Assignee {
    user_id: string;
    name: string;
    can_pick: boolean;
    /** ticket_type.ticket_category_id — drives SET-F-011 block filtering */
    ticket_category_id: string | null;
    assignment: {
        id: string;
        seat_plan_id: string;
        seat_id: string;
        seat_title: string | null;
    } | null;
}

interface MyTicket {
    id: string;
    ticket_type_name: string | null;
    is_group: boolean;
    assignees: Assignee[];
}

interface TakenSeat {
    id: string;
    seat_plan_id: string;
    seat_id: string;
    ticket_id: string;
    user_id: string;
    name: string | null;
    username: string | null;
    profile_emoji: string | null;
    short_bio: string | null;
    avatar_url: string | null;
    banner_url: string | null;
}

const props = defineProps<{
    event: { id: string; name: string; banner_image_urls: string[] };
    seatPlans: SeatPlan[];
    taken: TakenSeat[];
    myTickets: MyTicket[];
    context: { ticket_id: string | null; user_id: string | null };
}>();

const { t } = useI18n();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: t('seating.picker.breadcrumb'),
        href: pickerRoute(props.event.id).url,
    },
];

const activeTicketId = ref<string | null>(props.context.ticket_id);
const activeUserId = ref<string | null>(props.context.user_id);
const selectedSeat = ref<{
    planId: string;
    seatId: string;
    title: string;
} | null>(null);
const clickHint = ref<string | null>(null);
const viewerRef = ref<InstanceType<typeof SeatPlanViewer> | null>(null);
const viewer = useSeatPlanViewer(viewerRef);
const hoveredTaken = ref<TakenSeat | null>(null);
const hoverAnchor = ref<DOMRect | null>(null);

const form = useForm<{
    ticket_id: string | null;
    user_id: string | null;
    seat_plan_id: string | null;
    seat_id: string | null;
}>({
    ticket_id: null,
    user_id: null,
    seat_plan_id: null,
    seat_id: null,
});

const activeTicket = computed<MyTicket | null>(() => {
    if (activeTicketId.value === null) {
        return null;
    }

    return (
        props.myTickets.find((ticket) => ticket.id === activeTicketId.value) ??
        null
    );
});

const activeAssignee = computed<Assignee | null>(() => {
    if (!activeTicket.value || activeUserId.value === null) {
        return null;
    }

    return (
        activeTicket.value.assignees.find(
            (a) => a.user_id === activeUserId.value,
        ) ?? null
    );
});

const activePlanId = computed<number | null>(() => {
    if (activeAssignee.value?.assignment) {
        return activeAssignee.value.assignment.seat_plan_id;
    }

    return props.seatPlans[0]?.id ?? null;
});

const activePlan = computed<SeatPlan | null>(() => {
    if (activePlanId.value === null) {
        return null;
    }

    return (
        props.seatPlans.find((plan) => plan.id === activePlanId.value) ?? null
    );
});

const takenByPlanAndSeat = computed<Map<string, TakenSeat>>(() => {
    const map = new Map<string, TakenSeat>();

    for (const taken of props.taken) {
        map.set(`${taken.seat_plan_id}::${taken.seat_id}`, taken);
    }

    return map;
});

/**
 * Mirror of SeatingCategoryRules::blockAccepts on the server (SET-F-011).
 * Empty/missing allowed list ⇒ open to all categories (permissive default).
 */
function blockAcceptsCategory(
    block: SeatPlanBlock,
    categoryId: string | null,
): boolean {
    const allowed = (
        block as SeatPlanBlock & {
            allowed_ticket_category_ids?: number[] | null;
        }
    ).allowed_ticket_category_ids;

    if (!Array.isArray(allowed) || allowed.length === 0) {
        return true;
    }

    if (categoryId === null) {
        return false;
    }

    return allowed.includes(categoryId);
}

/**
 * Whether the block holding `seatId` is currently forbidden to the active
 * assignee's ticket category. Used to distinguish "seat taken" from
 * "category forbidden" at click time so we surface the right hint.
 */
function isSeatBlockedByCategory(
    seatId: string | string,
    plan: SeatPlan,
    categoryId: string | null,
): boolean {
    const seatIdStr = String(seatId);

    for (const block of plan.blocks ?? []) {
        if (block.seats.some((s) => String(s.id) === seatIdStr)) {
            return !blockAcceptsCategory(block, categoryId);
        }
    }

    return false;
}

const decoratedPlanData = computed<SeatPlanData | null>(() => {
    if (!activePlan.value) {
        return null;
    }

    const assigneeCategoryId = activeAssignee.value?.ticket_category_id ?? null;

    const blocks = (activePlan.value.blocks ?? []).map((block) => {
        // When the active assignee has a ticket category AND the block
        // restricts categories, mark every seat in the block as not salable
        // so it renders in the "taken" style and clicks are short-circuited.
        const blockBlockedByCategory =
            activeAssignee.value !== null &&
            !blockAcceptsCategory(block, assigneeCategoryId);

        return {
            ...block,
            seats: block.seats.map((seat: SeatPlanSeat) => {
                const key = `${activePlan.value!.id}::${seat.id}`;
                const taken = takenByPlanAndSeat.value.get(key);
                const isOwnAssignment =
                    activeAssignee.value?.assignment?.seat_plan_id ===
                        activePlan.value!.id &&
                    String(activeAssignee.value?.assignment?.seat_id) ===
                        String(seat.id);

                if (taken && !isOwnAssignment) {
                    return { ...seat, salable: false };
                }

                if (blockBlockedByCategory && !isOwnAssignment) {
                    return { ...seat, salable: false };
                }

                return seat;
            }),
        };
    });

    return {
        background_image_url: activePlan.value.background_image_url ?? null,
        labels: activePlan.value.labels ?? [],
        blocks,
    } as SeatPlanData;
});

const submitError = computed<string | null>(() => {
    return (
        form.errors.seat_id ??
        form.errors.seat_plan_id ??
        form.errors.user_id ??
        null
    );
});

/* Seats highlighted on the viewer: either the in-progress local pick or the
 * persisted assignment for the currently-focused person. Single-select by
 * design; the picker has never supported multi-seat selection. */
const selectedSeatIds = computed<(number | string)[]>(() => {
    if (selectedSeat.value) {
        return [selectedSeat.value.seatId];
    }

    if (
        activeAssignee.value?.assignment &&
        activeAssignee.value.assignment.seat_plan_id === activePlanId.value
    ) {
        return [activeAssignee.value.assignment.seat_id];
    }

    return [];
});

function flashHint(message: string): void {
    clickHint.value = message;
    window.setTimeout(() => {
        if (clickHint.value === message) {
            clickHint.value = null;
        }
    }, 4000);
}

function findSeatTitle(seatId: string | string): string | null {
    if (!activePlan.value) {
        return null;
    }

    const seatIdStr = String(seatId);

    for (const block of activePlan.value.blocks ?? []) {
        const seat = block.seats.find((s) => String(s.id) === seatIdStr);

        if (seat) {
            return (block.seat_title_prefix ?? '') + seat.title;
        }
    }

    return null;
}

function onSeatHoverEnter(payload: {
    id: string | string;
    rect: DOMRect;
}): void {
    if (!activePlan.value) {
        return;
    }

    const taken = takenByPlanAndSeat.value.get(
        `${activePlan.value.id}::${payload.id}`,
    );

    if (!taken?.username) {
        hoveredTaken.value = null;
        hoverAnchor.value = null;

        return;
    }

    hoveredTaken.value = taken;
    hoverAnchor.value = payload.rect;
}

function onSeatHoverLeave(): void {
    hoveredTaken.value = null;
    hoverAnchor.value = null;
}

function clearHighlight(): void {
    selectedSeat.value = null;
}

function selectContext(ticketId: string, userId: string): void {
    activeTicketId.value = ticketId;
    activeUserId.value = userId;
    clickHint.value = null;
    clearHighlight();
}

function onSeatClick(payload: {
    id: string | string;
    salable: boolean;
    rect: DOMRect;
}): void {
    if (!activePlan.value) {
        return;
    }

    /* Taken seat owned by a visible user → navigate to their profile instead
     * of running the pick flow. Hidden occupants fall through to the
     * salable=false branch and get the "seat taken" hint. */
    const takenAtClick = takenByPlanAndSeat.value.get(
        `${activePlan.value.id}::${payload.id}`,
    );

    if (takenAtClick?.username) {
        router.visit(profileShow({ username: takenAtClick.username }).url);

        return;
    }

    if (
        !payload.salable &&
        activeAssignee.value &&
        isSeatBlockedByCategory(
            payload.id,
            activePlan.value,
            activeAssignee.value.ticket_category_id,
        )
    ) {
        flashHint(t('seating.picker.hint.blockNotForCategory'));

        return;
    }

    if (!payload.salable) {
        flashHint(t('seating.picker.hint.seatTaken'));

        return;
    }

    if (!activeAssignee.value) {
        flashHint(t('seating.picker.hint.chooseAssigneeFirst'));

        return;
    }

    if (!activeAssignee.value.can_pick) {
        flashHint(t('seating.picker.hint.cannotPickForPerson'));

        return;
    }

    selectedSeat.value = {
        planId: activePlan.value.id,
        seatId: String(payload.id),
        title: findSeatTitle(payload.id) ?? String(payload.id),
    };
    clickHint.value = null;
}

function confirmSeat(): void {
    if (
        !selectedSeat.value ||
        !activeTicket.value ||
        activeUserId.value === null
    ) {
        return;
    }

    form.ticket_id = activeTicket.value.id;
    form.user_id = activeUserId.value;
    form.seat_plan_id = selectedSeat.value.planId;
    form.seat_id = selectedSeat.value.seatId;

    form.post(assignAction(props.event.id).url, {
        preserveScroll: true,
        // Don't preserve the component state — we want the fresh server-side
        // `myTickets` / `taken` props so the newly-assigned seat renders as
        // taken and the sidebar chip updates.
        preserveState: true,
        onSuccess: () => {
            clearHighlight();
            form.reset();
        },
        // onError: form.errors.* is populated automatically; the inline error
        // banner watches `submitError`.
    });
}

function releaseSeat(): void {
    if (!activeAssignee.value?.assignment) {
        return;
    }

    form.delete(
        releaseAction({
            event: props.event.id,
            assignment: activeAssignee.value.assignment.id,
        }).url,
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                clearHighlight();
            },
        },
    );
}

// Auto-select the only pickable assignee when no context was provided
// (user landed on the bare /events/{id}/seats URL but holds a single ticket
// with a single attendee — a common case for solo tickets).
onMounted(() => {
    if (activeTicketId.value !== null && activeUserId.value !== null) {
        return;
    }

    const pickable = props.myTickets.flatMap((ticket) =>
        ticket.assignees
            .filter((a) => a.can_pick)
            .map((a) => ({ ticketId: ticket.id, userId: a.user_id })),
    );

    if (pickable.length === 1) {
        activeTicketId.value = pickable[0].ticketId;
        activeUserId.value = pickable[0].userId;
    }
});

/* The saved-seat selection ring is driven by `selectedSeatIds` (derived from
 * the active assignee's persisted assignment), so picking a new assignee
 * implicitly re-highlights their seat. We still drop any in-progress local
 * pick so the user starts fresh for the newly-focused person. */
watch(activeAssignee, () => {
    clearHighlight();
});

function resetCanvasView(): void {
    viewer.fitToVenue({ animated: true });
}

function zoomToMySeat(): void {
    const savedSeatId = activeAssignee.value?.assignment?.seat_id;

    if (typeof savedSeatId === 'number') {
        viewer.zoomToSeat(savedSeatId, { animated: true });
    } else {
        viewer.fitToVenue({ animated: true });
    }
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="`${$t('seating.picker.headTitle')} — ${event.name}`" />

        <div class="flex h-full flex-col gap-4 p-4">
            <Heading
                :title="event.name"
                :description="$t('seating.picker.description')"
            />

            <div class="grid flex-1 gap-4 lg:grid-cols-[1fr_320px]">
                <!-- Seat map column -->
                <div class="flex flex-col gap-3">
                    <!-- Navigation toolbar -->
                    <div
                        class="flex flex-wrap items-center justify-between gap-2"
                    >
                        <span
                            v-if="activeAssignee"
                            class="text-sm text-muted-foreground"
                        >
                            {{ $t('seating.picker.seatFor') }}
                            <span class="font-medium text-foreground">{{
                                activeAssignee.name
                            }}</span>
                        </span>
                        <span v-else class="text-sm text-muted-foreground">
                            {{ $t('seating.picker.selectPersonHint') }}
                        </span>
                        <div
                            v-if="decoratedPlanData"
                            class="flex items-center gap-1"
                        >
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                class="gap-1.5"
                                @click="resetCanvasView"
                            >
                                <Maximize class="size-4" />
                                {{ $t('seating.picker.nav.resetView') }}
                            </Button>
                            <Button
                                v-if="activeAssignee?.assignment"
                                type="button"
                                variant="outline"
                                size="sm"
                                class="gap-1.5"
                                @click="zoomToMySeat"
                            >
                                <Target class="size-4" />
                                {{ $t('seating.picker.nav.zoomToMySeat') }}
                            </Button>
                        </div>
                    </div>

                    <div
                        v-if="decoratedPlanData"
                        class="overflow-hidden rounded-xl border bg-card"
                        style="height: 520px"
                    >
                        <SeatPlanViewer
                            ref="viewerRef"
                            :plan="decoratedPlanData"
                            :selected-seat-ids="selectedSeatIds"
                            show-legend
                            show-tooltip
                            @seat-click="onSeatClick"
                            @seat-hover-enter="onSeatHoverEnter"
                            @seat-hover-leave="onSeatHoverLeave"
                            @ready="notifyReady(viewer)"
                        />
                    </div>
                    <p
                        v-else
                        class="rounded-xl border border-dashed bg-card p-8 text-center text-sm text-muted-foreground"
                    >
                        {{ $t('seating.picker.noSeatPlan') }}
                    </p>

                    <!-- Always-rendered action bar. Stays inside the seat-map
                         column so it doesn't overlay the sidebar. -->
                    <div
                        class="flex flex-wrap items-center justify-between gap-3 rounded-xl border bg-card px-3 py-2 shadow-sm"
                    >
                        <div class="min-w-0 flex-1 space-y-0.5">
                            <div
                                v-if="submitError"
                                class="text-sm font-medium text-destructive"
                            >
                                {{ submitError }}
                            </div>
                            <div
                                v-else-if="clickHint"
                                class="text-sm font-medium text-amber-700 dark:text-amber-300"
                            >
                                {{ clickHint }}
                            </div>
                            <div class="text-sm">
                                <template v-if="selectedSeat">
                                    <span class="text-muted-foreground">{{
                                        $t('seating.picker.selectedSeat')
                                    }}</span>
                                    <span
                                        class="ml-1 font-mono font-semibold"
                                        >{{ selectedSeat.title }}</span
                                    >
                                </template>
                                <template
                                    v-else-if="activeAssignee?.assignment"
                                >
                                    <span class="text-muted-foreground">{{
                                        $t('seating.picker.currentSeat')
                                    }}</span>
                                    <span
                                        class="ml-1 font-mono font-semibold"
                                        >{{
                                            activeAssignee.assignment
                                                .seat_title ??
                                            activeAssignee.assignment.seat_id
                                        }}</span
                                    >
                                </template>
                                <template v-else>
                                    <span
                                        class="text-muted-foreground italic"
                                        >{{
                                            $t('seating.picker.nav.pickASeat')
                                        }}</span
                                    >
                                </template>
                            </div>
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            <Button
                                v-if="activeAssignee?.assignment"
                                type="button"
                                variant="outline"
                                size="sm"
                                :disabled="form.processing"
                                @click="releaseSeat"
                            >
                                {{ $t('seating.picker.releaseSeat') }}
                            </Button>
                            <Button
                                v-if="selectedSeat"
                                type="button"
                                size="sm"
                                :disabled="form.processing"
                                @click="confirmSeat"
                            >
                                {{
                                    form.processing
                                        ? $t('common.saving')
                                        : $t('seating.picker.confirmSeat')
                                }}
                            </Button>
                        </div>
                    </div>
                </div>

                <!-- Sidebar: pickable contexts -->
                <aside class="space-y-4">
                    <div class="rounded-xl border bg-card p-3">
                        <h2 class="mb-2 text-sm font-semibold">
                            {{ $t('seating.picker.yourTickets') }}
                        </h2>
                        <div
                            v-if="myTickets.length === 0"
                            class="text-sm text-muted-foreground"
                        >
                            {{ $t('seating.picker.noTickets') }}
                        </div>
                        <ul v-else class="space-y-3">
                            <li
                                v-for="ticket in myTickets"
                                :key="ticket.id"
                                class="space-y-2 rounded-lg border bg-background p-2"
                            >
                                <div
                                    class="flex items-center justify-between gap-2"
                                >
                                    <span class="truncate text-sm font-medium">
                                        {{ ticket.ticket_type_name }}
                                    </span>
                                    <Badge
                                        v-if="ticket.is_group"
                                        variant="outline"
                                        class="shrink-0 text-xs"
                                    >
                                        {{ $t('seating.picker.group') }}
                                    </Badge>
                                </div>
                                <div
                                    v-if="ticket.assignees.length === 0"
                                    class="text-xs text-muted-foreground"
                                >
                                    {{ $t('seating.picker.noAttendees') }}
                                </div>
                                <ul v-else class="space-y-1">
                                    <li
                                        v-for="assignee in ticket.assignees"
                                        :key="assignee.user_id"
                                    >
                                        <button
                                            type="button"
                                            class="flex w-full items-center justify-between gap-2 rounded-md px-2 py-1.5 text-left text-sm transition-colors hover:bg-muted disabled:cursor-not-allowed disabled:opacity-60"
                                            :class="{
                                                'bg-muted':
                                                    activeTicketId ===
                                                        ticket.id &&
                                                    activeUserId ===
                                                        assignee.user_id,
                                            }"
                                            :disabled="!assignee.can_pick"
                                            @click="
                                                selectContext(
                                                    ticket.id,
                                                    assignee.user_id,
                                                )
                                            "
                                        >
                                            <span
                                                class="flex items-center gap-2 truncate"
                                            >
                                                <Avatar class="size-7 shrink-0">
                                                    <AvatarFallback
                                                        class="text-xs"
                                                    >
                                                        {{
                                                            getInitials(
                                                                assignee.name,
                                                            )
                                                        }}
                                                    </AvatarFallback>
                                                </Avatar>
                                                <span class="truncate">
                                                    {{ assignee.name }}
                                                </span>
                                            </span>
                                            <span
                                                v-if="assignee.assignment"
                                                class="ml-2 inline-flex items-center gap-1 rounded bg-emerald-500/10 px-1.5 py-0.5 font-mono text-[10px] text-emerald-700 dark:text-emerald-400"
                                            >
                                                <Armchair class="size-3" />
                                                {{
                                                    assignee.assignment
                                                        .seat_title ??
                                                    assignee.assignment.seat_id
                                                }}
                                            </span>
                                        </button>
                                    </li>
                                </ul>
                            </li>
                        </ul>
                    </div>

                    <Button
                        as-child
                        variant="ghost"
                        size="sm"
                        class="w-full justify-start"
                    >
                        <a href="/portal/tickets">
                            <ChevronLeft class="size-4" />
                            {{ $t('seating.picker.backToTickets') }}
                        </a>
                    </Button>
                </aside>
            </div>
        </div>

        <SeatedUserHoverCard
            v-if="hoveredTaken && hoveredTaken.username && hoverAnchor"
            :seated-user="{
                username: hoveredTaken.username,
                profile_emoji: hoveredTaken.profile_emoji,
                short_bio: hoveredTaken.short_bio,
                avatar_url: hoveredTaken.avatar_url ?? '',
                banner_url: hoveredTaken.banner_url,
            }"
            :anchor-rect="hoverAnchor"
        />
    </AppLayout>
</template>
