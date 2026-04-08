<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import AnnouncementBanner from '@/components/announcements/AnnouncementBanner.vue';
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import DemoBanner from '@/components/demo/DemoBanner.vue';
import PushNotificationPrompt from '@/components/PushNotificationPrompt.vue';
import type { BreadcrumbItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();
const isAuthenticated = !!page.props.auth?.user;
</script>

<template>
    <AppShell variant="sidebar">
        <AppSidebar />
        <AppContent variant="sidebar" class="overflow-x-hidden">
            <DemoBanner />
            <AnnouncementBanner />
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <slot />
        </AppContent>
        <PushNotificationPrompt v-if="isAuthenticated" />
    </AppShell>
</template>
