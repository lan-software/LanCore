<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue';
import AnnouncementBanner from '@/components/announcements/AnnouncementBanner.vue';
import DemoBanner from '@/components/demo/DemoBanner.vue';

const bannersEl = ref<HTMLDivElement | null>(null);
let observer: ResizeObserver | null = null;

const setOffset = (px: number): void => {
    document.documentElement.style.setProperty(
        '--app-banner-offset',
        `${px}px`,
    );
};

onMounted((): void => {
    if (!bannersEl.value) {
        return;
    }
    observer = new ResizeObserver((entries): void => {
        setOffset(entries[0]?.contentRect.height ?? 0);
    });
    observer.observe(bannersEl.value);
});

onBeforeUnmount((): void => {
    observer?.disconnect();
    setOffset(0);
});
</script>

<template>
    <div class="flex min-h-screen flex-col">
        <div ref="bannersEl">
            <DemoBanner />
            <AnnouncementBanner />
        </div>
        <slot />
    </div>
</template>
