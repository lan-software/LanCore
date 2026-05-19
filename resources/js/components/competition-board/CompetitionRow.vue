<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { edit as competitionEdit } from '@/routes/competitions';
import RoundBlock from './RoundBlock.vue';
import SlackBadge from './SlackBadge.vue';
import SnapGrid from './SnapGrid.vue';
import StageBlock from './StageBlock.vue';
import type { CompetitionDto, StageScheduleDto } from './types';
import { useDragState } from './useDragState';

const props = defineProps<{
    competition: CompetitionDto;
    width: number;
    xForTime: (iso: string) => number;
    minuteForX: (x: number) => number;
    rangeStartMs: number;
    rangeEndMs: number;
    conflictedScheduleIds: Set<string>;
}>();

const dragState = useDragState();

const emit = defineEmits<{
    (e: 'open-stage', stageId: string): void;
    (e: 'open-round', roundId: string): void;
    (e: 'stage-drag-end', payload: { stageId: string; newStartIso: string }): void;
    (e: 'stage-resize-duration-end', payload: { stageId: string; minutes: number }): void;
    (e: 'stage-resize-reserve-end', payload: { stageId: string; minutes: number }): void;
    (e: 'round-drag-end', payload: { roundId: string; newStartIso: string }): void;
    (e: 'round-resize-duration-end', payload: { roundId: string; minutes: number }): void;
    (e: 'round-resize-reserve-end', payload: { roundId: string; minutes: number }): void;
    (e: 'drag-active-change', active: boolean): void;
}>();

// Visually distinguish a competition's stages with a stable colour wheel.
// The same stage_type within one competition reuses the same hue, so a
// SE + DE competition gets two clear colours regardless of order.
const STAGE_PALETTE = [
    '#10b981', // emerald
    '#0ea5e9', // sky
    '#a855f7', // violet
    '#f97316', // orange
    '#ec4899', // pink
    '#84cc16', // lime
] as const;

function colorForStage(stage: StageScheduleDto): string {
    const idx = props.competition.stage_schedules.findIndex((s) => s.id === stage.id);
    return STAGE_PALETTE[Math.max(0, idx) % STAGE_PALETTE.length];
}

function stageHasRounds(stage: StageScheduleDto): boolean {
    return stage.round_schedules.length > 0;
}
</script>

<template>
    <div class="flex border-b border-zinc-200 dark:border-zinc-800">
        <div class="sticky left-0 z-10 flex w-48 flex-col justify-center bg-white px-3 py-2 dark:bg-zinc-950">
            <Link
                :href="competitionEdit({ competition: competition.id }).url"
                class="truncate text-sm font-medium hover:text-emerald-600 hover:underline dark:hover:text-emerald-400"
            >
                {{ competition.name }}
            </Link>
            <div class="truncate text-[11px] text-zinc-500">{{ competition.game_name || '—' }}</div>
        </div>
        <div class="sticky left-48 z-10 flex w-20 items-center justify-center border-l border-zinc-200 bg-white px-2 py-2 dark:border-zinc-800 dark:bg-zinc-950">
            <SlackBadge :minutes="competition.current_slack_minutes" />
        </div>
        <div class="relative h-10 flex-1 border-l border-zinc-200 dark:border-zinc-800" :style="{ width: width + 'px' }">
            <SnapGrid
                :active="dragState.active.value"
                :width="width"
                :range-start-ms="rangeStartMs"
                :range-end-ms="rangeEndMs"
                :x-for-time="xForTime"
            />
            <template v-for="stage in competition.stage_schedules" :key="stage.id">
                <!--
                    Stage with rounds: render each round as a draggable box and put a small
                    stage label band above its first round. Stage without rounds (bracket
                    not generated yet): fall back to the legacy single-stage box.
                -->
                <template v-if="stageHasRounds(stage)">
                    <RoundBlock
                        v-for="round in stage.round_schedules"
                        :key="round.id"
                        :round="round"
                        :stage-name="stage.stage_name"
                        :stage-color="colorForStage(stage)"
                        :x-for-time="xForTime"
                        :minute-for-x="minuteForX"
                        :range-start-ms="rangeStartMs"
                        :conflicted="conflictedScheduleIds.has(round.id)"
                        @open="(id) => emit('open-round', id)"
                        @drag-end="(p) => emit('round-drag-end', p)"
                        @resize-duration-end="(p) => emit('round-resize-duration-end', p)"
                        @resize-reserve-end="(p) => emit('round-resize-reserve-end', p)"
                        @drag-active-change="(a) => emit('drag-active-change', a)"
                    />
                </template>
                <StageBlock
                    v-else
                    :stage="stage"
                    :x-for-time="xForTime"
                    :minute-for-x="minuteForX"
                    :range-start-ms="rangeStartMs"
                    :conflicted="conflictedScheduleIds.has(stage.id)"
                    @open="(id) => emit('open-stage', id)"
                    @drag-end="(p) => emit('stage-drag-end', p)"
                    @resize-duration-end="(p) => emit('stage-resize-duration-end', p)"
                    @resize-reserve-end="(p) => emit('stage-resize-reserve-end', p)"
                />
            </template>
        </div>
    </div>
</template>
