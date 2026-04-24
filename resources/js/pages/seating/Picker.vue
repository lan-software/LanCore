<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Armchair, ChevronLeft, Maximize, Target } from 'lucide-vue-next';
import { computed, nextTick, onMounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import {
    destroy as releaseAction,
    store as assignAction,
} from '@/actions/App/Domain/Seating/Http/Controllers/SeatPickerController';
import Heading from '@/components/Heading.vue';
import SeatMapCanvas from '@/components/SeatMapCanvas.vue';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { getInitials } from '@/composables/useInitials';
import AppLayout from '@/layouts/AppLayout.vue';
import { picker as pickerRoute } from '@/routes/events/seats';
import type { BreadcrumbItem } from '@/types';
import type { SeatPlanBlock, SeatPlanData, SeatPlanSeat } from '@/types/domain';

interface SeatPlan {
    id: number;
    name: string;
    data: SeatPlanData;
}

interface Assignee {
    user_id: number;
    name: string;
    can_pick: boolean;
    /** ticket_type.ticket_category_id — drives SET-F-011 block filtering */
    ticket_category_id: number | null;
    assignment: {
        id: number;
        seat_plan_id: number;
        seat_id: string;
        seat_title: string | null;
    } | null;
}

interface MyTicket {
    id: number;
    ticket_type_name: string | null;
    is_group: boolean;
    assignees: Assignee[];
}

interface TakenSeat {
    id: number;
    seat_plan_id: number;
    seat_id: string;
    ticket_id: number;
    user_id: number;
    name: string | null;
}

/**
 * Shape of the `seat_click` payload emitted by @alisaitteke/seatmap-canvas —
 * the library hands back the seat's own methods so callers can apply visual
 * selection themselves (see lib README §"Event Handling").
 */
interface LibrarySeat {
    id: string | number;
    title?: string;
    salable?: boolean;
    isSelected?: () => boolean;
    select?: () => void;
    unSelect?: () => void;
}

const props = defineProps<{
    event: { id: number; name: string; banner_image_urls: string[] };
    seatPlans: SeatPlan[];
    taken: TakenSeat[];
    myTickets: MyTicket[];
    context: { ticket_id: number | null; user_id: number | null };
}>();

const { t } = useI18n();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: t('seating.picker.breadcrumb'),
        href: pickerRoute(props.event.id).url,
    },
];

const activeTicketId = ref<number | null>(props.context.ticket_id);
const activeUserId = ref<number | null>(props.context.user_id);
const selectedSeat = ref<{
    planId: number;
    seatId: string;
    title: string;
} | null>(null);
const clickHint = ref<string | null>(null);
const canvasRef = ref<InstanceType<typeof SeatMapCanvas> | null>(null);
// Track the seat object the user most recently highlighted on the canvas so we
// can un-select it when they pick a different one (library allows multi-select
// by default; we only want single).
let highlightedSeat: LibrarySeat | null = null;

const form = useForm<{
    ticket_id: number | null;
    user_id: number | null;
    seat_plan_id: number | null;
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
    categoryId: number | null,
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
 * Given a library-synthesised seat payload, find whether its block is
 * currently blocked by the category filter. Used to distinguish "seat taken"
 * from "category forbidden" at click time.
 */
function isBlockBlockedByCategory(
    seat: LibrarySeat,
    plan: SeatPlan,
    categoryId: number | null,
): boolean {
    const seatIdStr = String(seat.id);

    for (const block of plan.data.blocks ?? []) {
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

    const blocks = (activePlan.value.data.blocks ?? []).map((block) => {
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
                    activeAssignee.value?.assignment?.seat_id ===
                        String(seat.id);

                if (taken && !isOwnAssignment) {
                    return {
                        ...seat,
                        salable: false,
                        title: taken.name
                            ? getInitials(taken.name)
                            : (seat.title ?? ''),
                    };
                }

                if (blockBlockedByCategory && !isOwnAssignment) {
                    return { ...seat, salable: false };
                }

                return seat;
            }),
        };
    });

    return { ...activePlan.value.data, blocks };
});

const submitError = computed<string | null>(() => {
    return (
        form.errors.seat_id ??
        form.errors.seat_plan_id ??
        form.errors.user_id ??
        null
    );
});

function flashHint(message: string): void {
    clickHint.value = message;
    window.setTimeout(() => {
        if (clickHint.value === message) {
            clickHint.value = null;
        }
    }, 4000);
}

function clearHighlight(): void {
    highlightedSeat?.unSelect?.();
    highlightedSeat = null;
    selectedSeat.value = null;
}

function selectContext(ticketId: number, userId: number): void {
    activeTicketId.value = ticketId;
    activeUserId.value = userId;
    clickHint.value = null;
    clearHighlight();
}

function onSeatClick(payload: unknown): void {
    const seat = payload as LibrarySeat;

    console.info('[Picker] onSeatClick', {
        seatId: seat.id,
        salable: seat.salable,
        activePlan: activePlan.value?.id ?? null,
        activeTicketId: activeTicketId.value,
        activeUserId: activeUserId.value,
        activeAssignee: activeAssignee.value
            ? {
                  user_id: activeAssignee.value.user_id,
                  can_pick: activeAssignee.value.can_pick,
              }
            : null,
    });

    // Guard: must have a plan on screen.
    if (!activePlan.value) {
        console.warn('[Picker] click rejected: no activePlan');

        return;
    }

    // Guard: if the clicked seat landed in a block that the active assignee's
    // ticket category is forbidden from, explain the rejection explicitly.
    // The seat's `salable` is already false (decoratedPlanData forces it), so
    // without this branch the user would get the generic "seat is taken"
    // message, which is misleading.
    if (
        seat.salable === false &&
        activeAssignee.value &&
        activePlan.value &&
        isBlockBlockedByCategory(
            seat,
            activePlan.value,
            activeAssignee.value.ticket_category_id,
        )
    ) {
        console.warn(
            '[Picker] click rejected: block does not accept this ticket category',
        );
        flashHint(t('seating.picker.hint.blockNotForCategory'));

        return;
    }

    // Guard: taken seat — tell the user why nothing happens.
    if (seat.salable === false) {
        console.warn('[Picker] click rejected: seat not salable');
        flashHint(t('seating.picker.hint.seatTaken'));

        return;
    }

    // Guard: no person chosen yet — point the user to the sidebar.
    if (!activeAssignee.value) {
        console.warn(
            '[Picker] click rejected: no activeAssignee (choose someone on the right first)',
        );
        flashHint(t('seating.picker.hint.chooseAssigneeFirst'));

        return;
    }

    // Guard: policy denies — typically because the viewer isn't owner/manager
    // and the assignee isn't themselves.
    if (!activeAssignee.value.can_pick) {
        console.warn(
            '[Picker] click rejected: can_pick=false for assignee',
            activeAssignee.value.user_id,
        );
        flashHint(t('seating.picker.hint.cannotPickForPerson'));

        return;
    }

    // All guards passed: apply visual selection exactly once.
    if (highlightedSeat && highlightedSeat !== seat) {
        highlightedSeat.unSelect?.();
    }

    if (!seat.isSelected?.()) {
        seat.select?.();
    }

    highlightedSeat = seat;

    selectedSeat.value = {
        planId: activePlan.value.id,
        seatId: String(seat.id),
        title: seat.title ?? String(seat.id),
    };
    clickHint.value = null;
    console.info('[Picker] seat accepted & selected', selectedSeat.value);
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

/**
 * When the active assignee already has a saved seat, highlight it on the canvas
 * so the user can see their current seat at a glance. Runs after the canvas
 * async-init completes.
 */
async function highlightSavedSeat(): Promise<void> {
    const assignment = activeAssignee.value?.assignment;

    if (!assignment || !activePlan.value) {
        return;
    }

    if (assignment.seat_plan_id !== activePlan.value.id) {
        return;
    }

    await nextTick();
    // Library init is async — poll briefly for the instance.
    const instance = await waitForInstance();

    if (!instance) {
        return;
    }

    // getSeat signature: (seatId, blockId). We have to search blocks for the match.
    for (const block of activePlan.value.data.blocks ?? []) {
        if (block.seats.some((s) => String(s.id) === assignment.seat_id)) {
            const seat = instance.getSeat?.(
                assignment.seat_id,
                String(block.id),
            );

            if (seat && typeof seat === 'object' && 'select' in seat) {
                (seat as LibrarySeat).select?.();
                highlightedSeat = seat as LibrarySeat;
            }

            return;
        }
    }
}

async function waitForInstance(): Promise<{
    getSeat?: (seatId: string, blockId: string) => unknown;
} | null> {
    for (let i = 0; i < 20; i++) {
        const instance = canvasRef.value?.getInstance?.();

        if (instance) {
            return instance as unknown as {
                getSeat?: (seatId: string, blockId: string) => unknown;
            };
        }

        await new Promise((r) => setTimeout(r, 50));
    }

    return null;
}

// Auto-select the only pickable assignee when no context was provided
// (user landed on the bare /events/{id}/seats URL but holds a single ticket
// with a single attendee — a common case for solo tickets).
onMounted(() => {
    if (activeTicketId.value !== null && activeUserId.value !== null) {
        highlightSavedSeat();

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
        highlightSavedSeat();
    }
});

// Re-highlight when the assignment context changes (e.g., user clicks a
// different person in the sidebar and that person already has a saved seat).
// Note: we DON'T rely only on this watcher after an assign/release — the
// canvas re-inits asynchronously (destroy + new instance), so by the time
// this watcher runs, the new library seat objects may not exist yet.
// The `@ready` handler (see `onCanvasReady`) is the authoritative re-highlight.
watch(activeAssignee, () => {
    clearHighlight();
});

/**
 * Called by SeatMapCanvas after each (re-)init completes. This is the reliable
 * moment to re-apply any visual selection tied to the assignee's saved seat,
 * because the canvas has just rebuilt and any seat references we had cached
 * are now stale.
 */
function onCanvasReady(): void {
    console.info('[Picker] canvas ready — re-applying saved-seat highlight');
    highlightedSeat = null;
    highlightSavedSeat();
}

function resetCanvasView(): void {
    canvasRef.value?.resetView?.();
}

function zoomToMySeat(): void {
    // Prefer the library's zoomToSelection (respects the seats we've marked
    // as selected via highlight). Falls back to resetting the view.
    const api = canvasRef.value;

    if (api?.zoomToSelection) {
        api.zoomToSelection();
    } else {
        api?.resetView?.();
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
                        <SeatMapCanvas
                            ref="canvasRef"
                            :data="decoratedPlanData"
                            :options="{
                                legend: true,
                                style: {
                                    seat: {
                                        hover: '#8fe100',
                                        color: '#6796ff',
                                        not_salable: '#424747',
                                        selected: '#56aa45',
                                    },
                                },
                            }"
                            @seat-click="onSeatClick"
                            @ready="onCanvasReady"
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
                        <a href="/tickets">
                            <ChevronLeft class="size-4" />
                            {{ $t('seating.picker.backToTickets') }}
                        </a>
                    </Button>
                </aside>
            </div>
        </div>
    </AppLayout>
</template>
