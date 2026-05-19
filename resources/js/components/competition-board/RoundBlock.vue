<script setup lang="ts">
import { computed, ref } from 'vue';
import type { RoundScheduleDto } from './types';
import { useDragState } from './useDragState';
import { snapMinutes } from './useTimeAxis';

const dragState = useDragState();
const dragging = ref(false);

const props = defineProps<{
    round: RoundScheduleDto;
    stageName: string;
    stageColor: string;
    xForTime: (iso: string) => number;
    minuteForX: (x: number) => number;
    rangeStartMs: number;
    conflicted: boolean;
}>();

const emit = defineEmits<{
    (e: 'open', roundId: string): void;
    (e: 'drag-end', payload: { roundId: string; newStartIso: string }): void;
    (e: 'resize-duration-end', payload: { roundId: string; minutes: number }): void;
    (e: 'resize-reserve-end', payload: { roundId: string; minutes: number }): void;
    (e: 'drag-active-change', active: boolean): void;
}>();

const hasStart = computed(() => !!props.round.starts_at);

const leftPx = computed(() => (hasStart.value ? props.xForTime(props.round.starts_at!) : 0));

const widthSolid = computed(() => {
    if (!hasStart.value) return 0;
    const start = new Date(props.round.starts_at!).getTime();
    const end = start + props.round.estimated_duration_minutes * 60_000;
    return props.xForTime(new Date(end).toISOString()) - leftPx.value;
});

const widthReserve = computed(() => {
    if (!hasStart.value) return 0;
    const start = new Date(props.round.starts_at!).getTime();
    const end = start + (props.round.estimated_duration_minutes + props.round.reserve_buffer_minutes) * 60_000;
    const solidEnd = start + props.round.estimated_duration_minutes * 60_000;
    return props.xForTime(new Date(end).toISOString()) - props.xForTime(new Date(solidEnd).toISOString());
});

const displayLabel = computed(() => props.round.label ?? `R${props.round.lanbrackets_round_number}`);

function formatHm(ms: number): string {
    const d = new Date(ms);
    return `${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`;
}

function startDrag(ev: PointerEvent, mode: 'move' | 'resize-duration' | 'resize-reserve') {
    if (!hasStart.value) return;
    ev.preventDefault();
    (ev.target as HTMLElement).setPointerCapture(ev.pointerId);
    const startX = ev.clientX;
    const startStartIsoMs = new Date(props.round.starts_at!).getTime();
    const startDuration = props.round.estimated_duration_minutes;
    const startReserve = props.round.reserve_buffer_minutes;
    const originalLeftPx = leftPx.value;
    const originalWidthSolid = widthSolid.value;
    const originalWidthReserve = widthReserve.value;

    let lastDeltaMinutes = 0;
    emit('drag-active-change', true);
    dragging.value = true;
    dragState.active.value = true;
    dragState.ghost.value = {
        x: originalLeftPx,
        widthSolid: originalWidthSolid,
        widthReserve: originalWidthReserve,
        color: props.stageColor,
    };

    function updateFeedback(e: PointerEvent) {
        if (mode === 'move') {
            const newStartMs = startStartIsoMs + lastDeltaMinutes * 60_000;
            const newEndMs = newStartMs + (startDuration + startReserve) * 60_000;
            const sign = lastDeltaMinutes >= 0 ? '+' : '';
            dragState.tooltip.value = {
                x: e.clientX,
                y: e.clientY,
                text: `${formatHm(newStartMs)} → ${formatHm(newEndMs)}  (${sign}${lastDeltaMinutes}m)`,
            };
            const newLeftPx = props.xForTime(new Date(newStartMs).toISOString());
            dragState.ghost.value = {
                x: newLeftPx,
                widthSolid: originalWidthSolid,
                widthReserve: originalWidthReserve,
                color: props.stageColor,
            };
        } else if (mode === 'resize-duration') {
            const newDuration = Math.max(5, startDuration + lastDeltaMinutes);
            const sign = lastDeltaMinutes >= 0 ? '+' : '';
            dragState.tooltip.value = {
                x: e.clientX,
                y: e.clientY,
                text: `${newDuration}m duration  (${sign}${lastDeltaMinutes}m)`,
            };
        } else {
            const newReserve = Math.max(0, startReserve + lastDeltaMinutes);
            const sign = lastDeltaMinutes >= 0 ? '+' : '';
            dragState.tooltip.value = {
                x: e.clientX,
                y: e.clientY,
                text: `${newReserve}m reserve  (${sign}${lastDeltaMinutes}m)`,
            };
        }
    }

    function onMove(e: PointerEvent) {
        const deltaPx = e.clientX - startX;
        const localStartPx = leftPx.value;
        const baselineMinutes = props.minuteForX(localStartPx);
        const targetMinutes = props.minuteForX(localStartPx + deltaPx);
        lastDeltaMinutes = snapMinutes(targetMinutes - baselineMinutes);
        updateFeedback(e);
    }

    function onUp() {
        window.removeEventListener('pointermove', onMove);
        window.removeEventListener('pointerup', onUp);
        emit('drag-active-change', false);
        dragging.value = false;
        dragState.active.value = false;
        dragState.tooltip.value = null;
        dragState.ghost.value = null;
        if (mode === 'move') {
            const newStart = new Date(startStartIsoMs + lastDeltaMinutes * 60_000).toISOString();
            emit('drag-end', { roundId: props.round.id, newStartIso: newStart });
        } else if (mode === 'resize-duration') {
            const newMinutes = Math.max(5, startDuration + lastDeltaMinutes);
            emit('resize-duration-end', { roundId: props.round.id, minutes: newMinutes });
        } else {
            const newMinutes = Math.max(0, startReserve + lastDeltaMinutes);
            emit('resize-reserve-end', { roundId: props.round.id, minutes: newMinutes });
        }
    }

    window.addEventListener('pointermove', onMove);
    window.addEventListener('pointerup', onUp);
}
</script>

<script lang="ts">
// `dragging` is set whenever this specific round block is the active drag source.
// The block lowers opacity while dragging; the ghost (rendered as a sibling) is
// the visual indicator of where the round will land.
</script>

<template>
    <div
        v-if="hasStart"
        class="group absolute top-1 flex h-8 select-none"
        :style="{ left: leftPx + 'px', opacity: dragState.ghost.value && dragState.ghost.value.x !== leftPx ? 0.3 : 1 }"
    >
        <button
            type="button"
            class="relative h-full rounded-sm border-t-2 px-2 text-xs font-medium text-white shadow-sm"
            :class="{ 'ring-2 ring-rose-500': conflicted }"
            :style="{
                width: widthSolid + 'px',
                cursor: 'grab',
                backgroundColor: stageColor,
                borderTopColor: stageColor,
            }"
            :title="`${stageName} · ${displayLabel}`"
            @click="emit('open', round.id)"
            @pointerdown="(e) => startDrag(e, 'move')"
        >
            <span class="truncate">{{ displayLabel }}</span>
            <span
                class="absolute top-0 right-0 h-full w-1.5 cursor-col-resize bg-black/20"
                @pointerdown.stop="(e) => startDrag(e, 'resize-duration')"
            />
        </button>
        <div
            v-if="widthReserve > 0"
            class="relative h-full"
            :style="{
                width: widthReserve + 'px',
                background: `repeating-linear-gradient(135deg, ${stageColor} 0 6px, transparent 6px 12px)`,
                opacity: 0.55,
            }"
        >
            <span
                class="absolute top-0 right-0 h-full w-1.5 cursor-col-resize bg-black/20"
                @pointerdown.stop="(e) => startDrag(e, 'resize-reserve')"
            />
        </div>
    </div>

    <!-- Ghost preview: a dashed-outline copy at the snapped drop position
         while this round is being dragged. -->
    <div
        v-if="dragging && dragState.ghost.value"
        class="pointer-events-none absolute top-1 flex h-8"
        :style="{ left: dragState.ghost.value.x + 'px' }"
    >
        <div
            class="h-full rounded-sm border-2 border-dashed"
            :style="{
                width: dragState.ghost.value.widthSolid + 'px',
                borderColor: dragState.ghost.value.color,
                backgroundColor: dragState.ghost.value.color + '33',
            }"
        />
        <div
            v-if="dragState.ghost.value.widthReserve > 0"
            class="h-full"
            :style="{
                width: dragState.ghost.value.widthReserve + 'px',
                background: `repeating-linear-gradient(135deg, ${dragState.ghost.value.color} 0 6px, transparent 6px 12px)`,
                opacity: 0.35,
            }"
        />
    </div>
</template>
