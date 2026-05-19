<script setup lang="ts">
defineProps<{
    visible: boolean;
    x: number;
    y: number;
    text: string;
}>();
</script>

<template>
    <div
        v-if="visible"
        class="pointer-events-none fixed z-50 rounded-md bg-zinc-900 px-2 py-1 text-xs font-medium text-white shadow-lg dark:bg-zinc-50 dark:text-zinc-900"
        :style="{ left: x + 16 + 'px', top: y + 16 + 'px' }"
    >
        {{ text }}
    </div>
</template>
