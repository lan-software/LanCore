<script setup lang="ts">
import { computed } from 'vue';
import { SNAP_MINUTES } from './useTimeAxis';

const props = defineProps<{
    active: boolean;
    width: number;
    rangeStartMs: number;
    rangeEndMs: number;
    xForTime: (iso: string) => number;
}>();

const ticks = computed(() => {
    if (!props.active) {
        return [] as number[];
    }

    const stepMs = SNAP_MINUTES * 60_000;
    const start = Math.ceil(props.rangeStartMs / stepMs) * stepMs;
    const out: number[] = [];

    for (let t = start; t < props.rangeEndMs; t += stepMs) {
        out.push(props.xForTime(new Date(t).toISOString()));
    }

    return out;
});
</script>

<template>
    <div
        v-if="active"
        class="pointer-events-none absolute top-0 left-0 h-full"
        :style="{ width: width + 'px' }"
    >
        <div
            v-for="(x, i) in ticks"
            :key="i"
            class="absolute top-0 h-full w-px bg-zinc-400/30 dark:bg-zinc-500/30"
            :style="{ left: x + 'px' }"
        />
    </div>
</template>
