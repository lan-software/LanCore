<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';

const page = usePage();

const logoUrl = computed<string | null>(
    () =>
        (page.props.organization as { logoUrl: string | null } | undefined)
            ?.logoUrl ?? null,
);

const orgName = computed<string | null>(
    () =>
        (page.props.organization as { name: string | null } | undefined)
            ?.name ?? null,
);
</script>

<template>
    <div
        class="flex aspect-square size-8 items-center justify-center rounded-md text-sidebar-primary-foreground"
    >
        <img
            v-if="logoUrl"
            :src="logoUrl"
            :alt="orgName ?? 'Organization logo'"
            class="size-8 object-contain"
        />
        <AppLogoIcon
            v-else
            class="size-5 fill-current text-white dark:text-black"
        />
    </div>
    <div class="ml-1 grid flex-1 text-left text-sm">
        <span class="mb-0.5 truncate leading-tight font-semibold">{{
            orgName ?? 'LanCore'
        }}</span>
    </div>
</template>
