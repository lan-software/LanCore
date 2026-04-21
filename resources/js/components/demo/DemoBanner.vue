<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

type DemoBannerProps = {
    message: string;
    mailpit_url: string | null;
};

const page = usePage<{ demoBanner: DemoBannerProps | null }>();
const banner = computed<DemoBannerProps | null>(
    () => page.props?.demoBanner ?? null,
);
</script>

<template>
    <div
        v-if="banner"
        class="sticky top-0 z-50 flex w-full items-center justify-center gap-3 border-b border-amber-300 bg-amber-200 px-4 py-2 text-sm font-medium text-amber-900 shadow-sm dark:border-amber-500/40 dark:bg-amber-500/15 dark:text-amber-100"
        role="alert"
    >
        <span>{{ banner.message }}</span>
        <a
            v-if="banner.mailpit_url"
            :href="banner.mailpit_url"
            target="_blank"
            rel="noopener noreferrer"
            class="underline underline-offset-2 hover:text-amber-950 dark:hover:text-amber-50"
        >
            Open Mailpit inbox
        </a>
    </div>
</template>
