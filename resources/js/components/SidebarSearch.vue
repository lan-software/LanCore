<script setup lang="ts">
// @see GitHub #14 — real-time search filter for the sidebar nav
import { Search, X } from 'lucide-vue-next';
import { useI18n } from 'vue-i18n';

const model = defineModel<string>({ default: '' });

const { t } = useI18n();
</script>

<template>
    <div class="relative px-2 group-data-[collapsible=icon]:hidden">
        <Search
            class="pointer-events-none absolute top-1/2 left-4 size-3.5 -translate-y-1/2 text-muted-foreground"
            aria-hidden="true"
        />
        <input
            v-model="model"
            type="text"
            :placeholder="t('navigation.search.placeholder')"
            :aria-label="t('navigation.search.label')"
            class="h-7 w-full rounded-md border border-sidebar-border/60 bg-sidebar-accent/30 pr-7 pl-7 text-sm transition-colors outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-1 focus-visible:ring-ring"
        />
        <button
            v-if="model.length > 0"
            type="button"
            class="absolute top-1/2 right-3 -translate-y-1/2 rounded-sm p-0.5 text-muted-foreground hover:text-foreground"
            :aria-label="t('navigation.search.clear')"
            @click="model = ''"
        >
            <X class="size-3.5" />
        </button>
    </div>
</template>
