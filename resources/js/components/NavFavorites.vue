<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { PinOff } from 'lucide-vue-next';
import { computed } from 'vue';
import { toggle as toggleFavorite } from '@/actions/App/Http/Controllers/Settings/SidebarFavoriteController';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuAction,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import type { NavItem } from '@/types';

const props = defineProps<{
    allItems: NavItem[];
}>();

const page = usePage();
const { isCurrentUrl } = useCurrentUrl();

const favorites = computed(() => {
    const favoriteIds: string[] = page.props.sidebarFavorites ?? [];

    return favoriteIds
        .map((id) => props.allItems.find((item) => item.id === id))
        .filter((item): item is NavItem => !!item);
});

function removeFavorite(itemId: string) {
    router.post(toggleFavorite().url, { item_id: itemId }, { preserveScroll: true, preserveState: true });
}
</script>

<template>
    <SidebarGroup v-if="favorites.length > 0" class="px-2 py-0">
        <SidebarGroupLabel>Favorites</SidebarGroupLabel>
        <SidebarMenu>
            <SidebarMenuItem v-for="item in favorites" :key="item.id">
                <SidebarMenuButton
                    as-child
                    :is-active="isCurrentUrl(item.href)"
                    :tooltip="item.title"
                >
                    <Link :href="item.href">
                        <component :is="item.icon" />
                        <span>{{ item.title }}</span>
                    </Link>
                </SidebarMenuButton>
                <SidebarMenuAction :show-on-hover="true" @click="removeFavorite(item.id!)">
                    <PinOff class="size-4" />
                    <span class="sr-only">Unpin {{ item.title }}</span>
                </SidebarMenuAction>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>
