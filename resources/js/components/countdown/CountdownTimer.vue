<script setup lang="ts">
import { useNow } from '@vueuse/core';
import { computed } from 'vue';

const props = defineProps<{
    /** ISO-8601 timestamp the countdown is targeting. */
    targetIso: string;
    /** Reactive ticker; defaults to a 1-second internal `useNow`. */
}>();

const now = useNow({ interval: 1000 });

const remaining = computed(() => {
    const target = new Date(props.targetIso).getTime();
    const diffMs = Math.max(0, target - now.value.getTime());
    const totalSeconds = Math.floor(diffMs / 1000);

    return {
        days: Math.floor(totalSeconds / 86400),
        hours: Math.floor((totalSeconds % 86400) / 3600),
        minutes: Math.floor((totalSeconds % 3600) / 60),
        seconds: totalSeconds % 60,
        elapsed: diffMs === 0,
    };
});

const segments = computed(() => [
    { label: 'Days', value: remaining.value.days },
    { label: 'Hours', value: remaining.value.hours },
    { label: 'Minutes', value: remaining.value.minutes },
    { label: 'Seconds', value: remaining.value.seconds },
]);
</script>

<template>
    <div
        class="grid grid-cols-2 gap-3 sm:grid-cols-4"
        role="timer"
        aria-live="polite"
    >
        <div
            v-for="segment in segments"
            :key="segment.label"
            class="rounded-lg border bg-card px-4 py-6 text-center shadow-sm"
        >
            <div class="text-4xl font-semibold tabular-nums sm:text-5xl">
                {{ segment.value.toString().padStart(2, '0') }}
            </div>
            <div
                class="mt-2 text-xs tracking-wider text-muted-foreground uppercase"
            >
                {{ segment.label }}
            </div>
        </div>
    </div>
</template>
