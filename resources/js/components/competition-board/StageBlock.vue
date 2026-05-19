<script setup lang="ts">
import { computed } from 'vue';
import type { StageScheduleDto } from './types';

const props = defineProps<{
    stage: StageScheduleDto;
    xForTime: (iso: string) => number;
    minuteForX: (x: number) => number;
    rangeStartMs: number;
    conflicted: boolean;
}>();

const emit = defineEmits<{
    (e: 'open', stageId: string): void;
    (e: 'drag-end', payload: { stageId: string; newStartIso: string }): void;
    (e: 'resize-duration-end', payload: { stageId: string; minutes: number }): void;
    (e: 'resize-reserve-end', payload: { stageId: string; minutes: number }): void;
}>();

const hasStart = computed(() => !!props.stage.starts_at);

const leftPx = computed(() => (hasStart.value ? props.xForTime(props.stage.starts_at!) : 0));

const widthSolid = computed(() => {
    if (!hasStart.value) return 0;
    const start = new Date(props.stage.starts_at!).getTime();
    const end = start + props.stage.estimated_duration_minutes * 60_000;
    return props.xForTime(new Date(end).toISOString()) - leftPx.value;
});

const widthReserve = computed(() => {
    if (!hasStart.value) return 0;
    const start = new Date(props.stage.starts_at!).getTime();
    const end = start + (props.stage.estimated_duration_minutes + props.stage.reserve_buffer_minutes) * 60_000;
    const solidEnd = start + props.stage.estimated_duration_minutes * 60_000;
    return props.xForTime(new Date(end).toISOString()) - props.xForTime(new Date(solidEnd).toISOString());
});

function snapMinutes(mins: number): number {
    return Math.round(mins / 15) * 15;
}

function startDrag(ev: PointerEvent, mode: 'move' | 'resize-duration' | 'resize-reserve') {
    if (!hasStart.value) return;
    ev.preventDefault();
    (ev.target as HTMLElement).setPointerCapture(ev.pointerId);
    const startX = ev.clientX;
    const startStartIsoMs = new Date(props.stage.starts_at!).getTime();
    const startDuration = props.stage.estimated_duration_minutes;
    const startReserve = props.stage.reserve_buffer_minutes;

    let lastDeltaMinutes = 0;

    function onMove(e: PointerEvent) {
        const deltaPx = e.clientX - startX;
        const localStartPx = leftPx.value;
        const baselineMinutes = props.minuteForX(localStartPx);
        const targetMinutes = props.minuteForX(localStartPx + deltaPx);
        lastDeltaMinutes = snapMinutes(targetMinutes - baselineMinutes);
    }

    function onUp() {
        window.removeEventListener('pointermove', onMove);
        window.removeEventListener('pointerup', onUp);
        if (mode === 'move') {
            const newStart = new Date(startStartIsoMs + lastDeltaMinutes * 60_000).toISOString();
            emit('drag-end', { stageId: props.stage.id, newStartIso: newStart });
        } else if (mode === 'resize-duration') {
            const newMinutes = Math.max(5, startDuration + lastDeltaMinutes);
            emit('resize-duration-end', { stageId: props.stage.id, minutes: newMinutes });
        } else {
            const newMinutes = Math.max(0, startReserve + lastDeltaMinutes);
            emit('resize-reserve-end', { stageId: props.stage.id, minutes: newMinutes });
        }
    }

    window.addEventListener('pointermove', onMove);
    window.addEventListener('pointerup', onUp);
}
</script>

<template>
    <div
        v-if="hasStart"
        class="group absolute top-1 flex h-8 select-none"
        :style="{ left: leftPx + 'px' }"
    >
        <button
            type="button"
            class="relative h-full rounded-sm bg-emerald-500/90 px-2 text-xs font-medium text-white shadow-sm hover:bg-emerald-500 dark:bg-emerald-600 dark:hover:bg-emerald-500"
            :class="{ 'ring-2 ring-rose-500': conflicted }"
            :style="{ width: widthSolid + 'px', cursor: 'grab' }"
            :title="stage.stage_name"
            @click="emit('open', stage.id)"
            @pointerdown="(e) => startDrag(e, 'move')"
        >
            <span class="truncate">{{ stage.stage_name }}</span>
            <span
                class="absolute top-0 right-0 h-full w-1.5 cursor-col-resize bg-emerald-700/40"
                @pointerdown.stop="(e) => startDrag(e, 'resize-duration')"
            />
        </button>
        <div
            v-if="widthReserve > 0"
            class="relative h-full"
            :style="{
                width: widthReserve + 'px',
                background:
                    'repeating-linear-gradient(135deg, rgba(16,185,129,0.55) 0 6px, rgba(16,185,129,0.15) 6px 12px)',
            }"
        >
            <span
                class="absolute top-0 right-0 h-full w-1.5 cursor-col-resize bg-emerald-700/40"
                @pointerdown.stop="(e) => startDrag(e, 'resize-reserve')"
            />
        </div>
    </div>
    <div
        v-else
        class="absolute top-1 flex h-8 items-center gap-1 px-2 text-[11px] text-zinc-500 italic"
        :style="{ left: '4px' }"
    >
        {{ stage.stage_name }}
    </div>
</template>
