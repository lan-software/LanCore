<script setup lang="ts">
import { computed } from 'vue';
import type { TimeSegment } from './types';

const props = defineProps<{
    segments: TimeSegment[];
    ticks: Array<{ x: number; label: string; segment: 'busy' | 'idle' }>;
    width: number;
}>();

const styleWidth = computed(() => `${props.width}px`);
</script>

<template>
    <div
        class="sticky top-0 z-10 border-b border-zinc-200 bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900"
        :style="{ width: styleWidth }"
    >
        <div class="relative h-10">
            <div
                v-for="seg in segments"
                :key="seg.start"
                class="absolute top-0 h-full"
                :class="
                    seg.classification === 'busy'
                        ? 'bg-zinc-100 dark:bg-zinc-800/60'
                        : 'bg-zinc-200/40 dark:bg-zinc-800/30'
                "
                :style="{
                    left: seg.pxOffset + 'px',
                    width:
                        ((seg.end - seg.start) / 60_000) * seg.pxPerMinute +
                        'px',
                }"
            />
            <div
                v-for="(tick, idx) in ticks"
                :key="idx"
                class="absolute top-0 flex h-full flex-col justify-end pb-1"
                :style="{ left: tick.x + 'px', transform: 'translateX(-1px)' }"
            >
                <div class="h-2 w-px bg-zinc-400 dark:bg-zinc-500" />
                <div
                    class="ml-1 text-[10px] leading-none text-zinc-600 dark:text-zinc-400"
                >
                    {{ tick.label }}
                </div>
            </div>
        </div>
    </div>
</template>
