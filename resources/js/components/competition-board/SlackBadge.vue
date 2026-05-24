<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    minutes: number | null;
}>();

const display = computed(() => {
    if (props.minutes === null) {
        return '—';
    }

    const sign = props.minutes >= 0 ? '+' : '−';
    const abs = Math.abs(props.minutes);
    const h = Math.floor(abs / 60);
    const m = abs % 60;

    return `${sign}${h}:${String(m).padStart(2, '0')}`;
});

const tone = computed(() => {
    if (props.minutes === null) {
        return 'text-zinc-500';
    }

    if (props.minutes < 0) {
        return 'text-rose-600 dark:text-rose-400 font-semibold';
    }

    if (props.minutes < 30) {
        return 'text-amber-600 dark:text-amber-400';
    }

    return 'text-emerald-600 dark:text-emerald-400';
});
</script>

<template>
    <span class="font-mono text-xs tabular-nums" :class="tone">{{
        display
    }}</span>
</template>
