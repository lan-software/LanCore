<script setup lang="ts">
// @see GitHub #14 — collapsible sidebar groups with persisted state + search filter
import { router, usePage } from '@inertiajs/vue3';
import { ChevronDown } from 'lucide-vue-next';
import { computed, useSlots } from 'vue';
import { toggle as toggleGroupAction } from '@/actions/App/Http/Controllers/Settings/SidebarGroupStateController';
import {
    SidebarGroup,
    SidebarGroupContent,
    SidebarGroupLabel,
} from '@/components/ui/sidebar';

const props = defineProps<{
    /** Stable id used to persist collapse state per user. */
    groupId: string;
    label: string;
    /** Plain-text labels of every item rendered in the default slot, used for search filtering. */
    itemLabels: string[];
    /** Outer `v-if` condition — when false, render nothing (mirrors prior gating). */
    visible: boolean;
    /** Active search query — when non-empty, group is filtered + force-expanded. */
    searchQuery: string;
}>();

const page = usePage();
const slots = useSlots();

const collapsedGroups = computed<string[]>(
    () => page.props.sidebarCollapsedGroups ?? [],
);

const persistedCollapsed = computed<boolean>(() =>
    collapsedGroups.value.includes(props.groupId),
);

const normalizedQuery = computed<string>(() =>
    props.searchQuery.trim().toLowerCase(),
);

const matchesSearch = computed<boolean>(() => {
    if (normalizedQuery.value === '') return true;
    return props.itemLabels.some((label) =>
        label.toLowerCase().includes(normalizedQuery.value),
    );
});

// When a search is active, force the group open so matching items are visible.
const expanded = computed<boolean>(() =>
    normalizedQuery.value !== '' ? true : !persistedCollapsed.value,
);

function toggle(): void {
    router.post(
        toggleGroupAction().url,
        { group_id: props.groupId },
        { preserveScroll: true, preserveState: true },
    );
}

const hasSlot = computed<boolean>(() => !!slots.default);
</script>

<template>
    <SidebarGroup v-if="visible && matchesSearch && hasSlot">
        <button
            type="button"
            class="group/collapse flex w-full items-center justify-between rounded-md px-2 py-1 text-left transition-colors hover:bg-sidebar-accent/40"
            :aria-expanded="expanded"
            @click="toggle"
        >
            <SidebarGroupLabel as="span" class="pointer-events-none">
                {{ label }}
            </SidebarGroupLabel>
            <ChevronDown
                class="size-4 shrink-0 text-muted-foreground transition-transform"
                :class="expanded ? '' : '-rotate-90'"
                aria-hidden="true"
            />
        </button>
        <SidebarGroupContent v-show="expanded">
            <slot />
        </SidebarGroupContent>
    </SidebarGroup>
</template>
