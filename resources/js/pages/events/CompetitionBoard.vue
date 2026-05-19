<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { useEcho } from '@laravel/echo-vue';
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import CompetitionRow from '@/components/competition-board/CompetitionRow.vue';
import DragTooltip from '@/components/competition-board/DragTooltip.vue';
import SnapGrid from '@/components/competition-board/SnapGrid.vue';
import StageDetailPanel from '@/components/competition-board/StageDetailPanel.vue';
import TimeAxis from '@/components/competition-board/TimeAxis.vue';
import type {
    CompetitionDto,
    ConflictDto,
    EventDto,
    RoundScheduleDto,
    StageScheduleDto,
} from '@/components/competition-board/types';
import { provideDragState } from '@/components/competition-board/useDragState';
import { useTimeAxis } from '@/components/competition-board/useTimeAxis';
import Heading from '@/components/Heading.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';

const props = defineProps<{
    event: EventDto;
    competitions: CompetitionDto[];
    conflicts: ConflictDto[];
}>();

const { t } = useI18n();

const competitions = ref<CompetitionDto[]>(props.competitions);
const conflicts = ref<ConflictDto[]>(props.conflicts);

watch(
    () => props.competitions,
    (next) => {
        competitions.value = next;
    },
);
watch(
    () => props.conflicts,
    (next) => {
        conflicts.value = next;
    },
);

const { segments, totalWidth, rangeStart, rangeEnd, ticks, xForTime, minuteForX } = useTimeAxis(
    () => props.event,
    () => competitions.value,
);

const dragState = provideDragState();

const conflictedScheduleIds = computed(() => {
    const set = new Set<string>();
    for (const c of conflicts.value) {
        for (const id of c.schedule_ids) set.add(id);
    }
    return set;
});

const panelOpen = ref(false);
const panelStage = ref<StageScheduleDto | null>(null);

function openStage(stageId: string) {
    for (const c of competitions.value) {
        const s = c.stage_schedules.find((s) => s.id === stageId);
        if (s) {
            panelStage.value = s;
            panelOpen.value = true;
            return;
        }
    }
}

function openRound(roundId: string) {
    for (const c of competitions.value) {
        for (const s of c.stage_schedules) {
            const r = s.round_schedules.find((r) => r.id === roundId);
            if (r) {
                // For now, opening a round opens the parent stage panel.
                // A dedicated RoundDetailPanel can replace this in a follow-up.
                panelStage.value = s;
                panelOpen.value = true;
                return;
            }
        }
    }
}

function patchStage(
    stageId: string,
    patch: Record<string, unknown>,
    optimistic?: (s: StageScheduleDto) => void,
) {
    for (const c of competitions.value) {
        const s = c.stage_schedules.find((x) => x.id === stageId);
        if (s && optimistic) optimistic(s);
    }
    router.patch(`/backstage/stage-schedules/${stageId}`, patch, {
        preserveScroll: true,
        preserveState: true,
        only: ['competitions', 'conflicts'],
        onError: () => refreshFromServer(),
    });
}

function patchRound(
    roundId: string,
    patch: Record<string, unknown>,
    optimistic?: (r: RoundScheduleDto) => void,
) {
    for (const c of competitions.value) {
        for (const s of c.stage_schedules) {
            const r = s.round_schedules.find((r) => r.id === roundId);
            if (r && optimistic) optimistic(r);
        }
    }
    router.patch(`/backstage/round-schedules/${roundId}`, patch, {
        preserveScroll: true,
        preserveState: true,
        only: ['competitions', 'conflicts'],
        onError: () => refreshFromServer(),
    });
}

function refreshFromServer() {
    router.reload({ only: ['competitions', 'conflicts'] });
}

useEcho(`event.${props.event.id}.competition-board`, '.stage-schedule.updated', () => {
    refreshFromServer();
});
useEcho(`event.${props.event.id}.competition-board`, '.round-schedule.updated', () => {
    refreshFromServer();
});

function sync() {
    router.post(`/backstage/competition-board/sync`, {}, { preserveScroll: true });
}
function autoFit() {
    router.post(`/backstage/competition-board/auto-fit`, {}, { preserveScroll: true });
}
</script>

<template>
    <AppLayout>
        <Head :title="t('competition_board.title')" />
        <div class="space-y-3 p-4">
            <Heading :title="t('competition_board.title')" :description="t('competition_board.subtitle')" />
            <div class="flex gap-2">
                <Button variant="secondary" size="sm" @click="sync">
                    {{ t('competition_board.actions.sync') }}
                </Button>
                <Button variant="secondary" size="sm" @click="autoFit">
                    {{ t('competition_board.actions.auto_fit') }}
                </Button>
            </div>

            <div v-if="conflicts.length > 0" class="space-y-1">
                <Alert v-for="(c, idx) in conflicts" :key="idx" :variant="c.severity === 'error' ? 'destructive' : 'default'">
                    <AlertDescription>{{ t(c.message_key, c.params as Record<string, unknown>) }}</AlertDescription>
                </Alert>
            </div>

            <div class="rounded-md border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-950">
                <div class="overflow-x-auto">
                    <div class="flex border-b border-zinc-200 dark:border-zinc-800">
                        <div class="sticky left-0 z-20 flex w-48 items-center bg-zinc-50 px-3 text-[11px] font-semibold uppercase tracking-wide text-zinc-500 dark:bg-zinc-900">
                            {{ t('competition_board.column.competition') }}
                        </div>
                        <div class="sticky left-48 z-20 flex w-20 items-center justify-center border-l border-zinc-200 bg-zinc-50 px-2 text-[11px] font-semibold uppercase tracking-wide text-zinc-500 dark:border-zinc-800 dark:bg-zinc-900">
                            {{ t('competition_board.column.slack') }}
                        </div>
                        <TimeAxis :segments="segments" :ticks="ticks" :width="totalWidth" />
                    </div>
                    <CompetitionRow
                        v-for="competition in competitions"
                        :key="competition.id"
                        :competition="competition"
                        :width="totalWidth"
                        :x-for-time="xForTime"
                        :minute-for-x="minuteForX"
                        :range-start-ms="rangeStart"
                        :range-end-ms="rangeEnd"
                        :conflicted-schedule-ids="conflictedScheduleIds"
                        @open-stage="openStage"
                        @open-round="openRound"
                        @stage-drag-end="(p) => patchStage(p.stageId, { starts_at: p.newStartIso }, (s) => (s.starts_at = p.newStartIso))"
                        @stage-resize-duration-end="(p) => patchStage(p.stageId, { estimated_duration_minutes: p.minutes }, (s) => (s.estimated_duration_minutes = p.minutes))"
                        @stage-resize-reserve-end="(p) => patchStage(p.stageId, { reserve_buffer_minutes: p.minutes }, (s) => (s.reserve_buffer_minutes = p.minutes))"
                        @round-drag-end="(p) => patchRound(p.roundId, { starts_at: p.newStartIso }, (r) => (r.starts_at = p.newStartIso))"
                        @round-resize-duration-end="(p) => patchRound(p.roundId, { estimated_duration_minutes: p.minutes }, (r) => (r.estimated_duration_minutes = p.minutes))"
                        @round-resize-reserve-end="(p) => patchRound(p.roundId, { reserve_buffer_minutes: p.minutes }, (r) => (r.reserve_buffer_minutes = p.minutes))"
                    />
                </div>
            </div>
        </div>

        <StageDetailPanel
            :open="panelOpen"
            :stage="panelStage"
            @close="panelOpen = false"
            @save="(p) => { patchStage(p.stageId, p); panelOpen = false; }"
        />

        <!-- Drag UX overlay: live time tooltip following the cursor. The 15-min
             snap grid lives inside each CompetitionRow because it needs the row's
             coordinate space, not the page's. -->
        <DragTooltip
            :visible="dragState.tooltip.value !== null"
            :x="dragState.tooltip.value?.x ?? 0"
            :y="dragState.tooltip.value?.y ?? 0"
            :text="dragState.tooltip.value?.text ?? ''"
        />
    </AppLayout>
</template>
