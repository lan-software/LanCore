<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { edit as competitionEdit } from '@/routes/competitions';
import SlackBadge from './SlackBadge.vue';
import StageBlock from './StageBlock.vue';
import type { CompetitionDto } from './types';

defineProps<{
    competition: CompetitionDto;
    width: number;
    xForTime: (iso: string) => number;
    minuteForX: (x: number) => number;
    rangeStartMs: number;
    conflictedScheduleIds: Set<string>;
}>();

const emit = defineEmits<{
    (e: 'open-stage', stageId: string): void;
    (e: 'drag-end', payload: { stageId: string; newStartIso: string }): void;
    (e: 'resize-duration-end', payload: { stageId: string; minutes: number }): void;
    (e: 'resize-reserve-end', payload: { stageId: string; minutes: number }): void;
}>();
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
            <StageBlock
                v-for="stage in competition.stage_schedules"
                :key="stage.id"
                :stage="stage"
                :x-for-time="xForTime"
                :minute-for-x="minuteForX"
                :range-start-ms="rangeStartMs"
                :conflicted="conflictedScheduleIds.has(stage.id)"
                @open="(id) => emit('open-stage', id)"
                @drag-end="(p) => emit('drag-end', p)"
                @resize-duration-end="(p) => emit('resize-duration-end', p)"
                @resize-reserve-end="(p) => emit('resize-reserve-end', p)"
            />
        </div>
    </div>
</template>
