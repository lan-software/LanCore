<script setup lang="ts">
import AnnouncementBanner from '@/components/announcements/AnnouncementBanner.vue';
import DemoBanner from '@/components/demo/DemoBanner.vue';
</script>

<template>
    <div class="flex min-h-screen flex-col">
        <DemoBanner />
        <AnnouncementBanner />
        <slot />
    </div>
</template>
