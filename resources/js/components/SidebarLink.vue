<script setup lang="ts">
// @see GitHub #14 — unified row that pairs a link with a pin/favorite action.
// Hides itself when the search query doesn't match the link's label so the
// CollapsibleSidebarGroup naturally collapses to a filtered subset.
import { Link, router, usePage } from '@inertiajs/vue3';
import { Pin, PinOff } from 'lucide-vue-next';
import type { Component } from 'vue';
import { computed } from 'vue';
import { toggle as toggleFavoriteAction } from '@/actions/App/Http/Controllers/Settings/SidebarFavoriteController';
import {
    SidebarMenuAction,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';

const props = defineProps<{
    /** Stable id used for pin/favorite persistence. Omit to hide the pin action (rare). */
    favoriteId?: string;
    label: string;
    icon: Component;
    href?: string;
    /** Use for external links (e.g. /horizon) instead of Inertia <Link>. */
    externalHref?: string;
    /** Active search filter — when set and label doesn't match, the row is hidden. */
    searchQuery?: string;
}>();

const page = usePage();

const favorites = computed<string[]>(
    () => page.props.sidebarFavorites ?? [],
);

const isFavorited = computed<boolean>(() =>
    props.favoriteId ? favorites.value.includes(props.favoriteId) : false,
);

const matchesSearch = computed<boolean>(() => {
    const q = (props.searchQuery ?? '').trim().toLowerCase();
    if (q === '') return true;
    return props.label.toLowerCase().includes(q);
});

function toggleFavorite(): void {
    if (!props.favoriteId) return;
    router.post(
        toggleFavoriteAction().url,
        { item_id: props.favoriteId },
        { preserveScroll: true, preserveState: true },
    );
}
</script>

<template>
    <SidebarMenuItem v-if="matchesSearch">
        <SidebarMenuButton as-child>
            <a
                v-if="externalHref"
                :href="externalHref"
                target="_blank"
                rel="noopener"
            >
                <component :is="icon" />
                <span>{{ label }}</span>
            </a>
            <Link v-else-if="href" :href="href">
                <component :is="icon" />
                <span>{{ label }}</span>
            </Link>
        </SidebarMenuButton>
        <SidebarMenuAction
            v-if="favoriteId"
            :show-on-hover="true"
            @click="toggleFavorite"
        >
            <PinOff v-if="isFavorited" class="size-4" />
            <Pin v-else class="size-4" />
        </SidebarMenuAction>
    </SidebarMenuItem>
</template>
