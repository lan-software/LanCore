<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { ChevronsUpDown } from 'lucide-vue-next';
import { computed } from 'vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import UserInfo from '@/components/UserInfo.vue';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { cn } from '@/lib/utils';

type Props = {
    variant?: 'sidebar' | 'topbar';
};

const props = withDefaults(defineProps<Props>(), {
    variant: 'sidebar',
});

const page = usePage();
const user = computed(() => page.props.auth.user);
const sidebar = props.variant === 'sidebar' ? useSidebar() : null;

const dropdownSide = computed(() => {
    if (props.variant === 'topbar') {
        return 'bottom';
    }

    if (sidebar?.isMobile.value) {
        return 'bottom';
    }

    return sidebar?.state.value === 'collapsed' ? 'left' : 'bottom';
});
</script>

<template>
    <SidebarMenu v-if="props.variant === 'sidebar'">
        <SidebarMenuItem>
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <SidebarMenuButton
                        size="lg"
                        class="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground"
                        data-test="sidebar-menu-button"
                    >
                        <UserInfo :user="user" />
                        <ChevronsUpDown class="ml-auto size-4" />
                    </SidebarMenuButton>
                </DropdownMenuTrigger>
                <DropdownMenuContent
                    class="w-(--reka-dropdown-menu-trigger-width) min-w-56 rounded-lg"
                    :side="dropdownSide"
                    align="end"
                    :side-offset="4"
                >
                    <UserMenuContent :user="user" />
                </DropdownMenuContent>
            </DropdownMenu>
        </SidebarMenuItem>
    </SidebarMenu>

    <DropdownMenu v-else>
        <DropdownMenuTrigger as-child>
            <button
                type="button"
                :class="
                    cn(
                        'flex items-center gap-2 rounded-full border border-border/70 bg-background/80 px-2 py-1.5 text-sm shadow-sm transition hover:bg-accent hover:text-accent-foreground sm:gap-3 sm:px-3 sm:py-2',
                    )
                "
                data-test="topbar-user-button"
            >
                <UserInfo :user="user" :hide-details-on-mobile="true" />
                <ChevronsUpDown class="size-4 text-muted-foreground sm:block" />
            </button>
        </DropdownMenuTrigger>
        <DropdownMenuContent
            class="min-w-64 rounded-lg"
            :side="dropdownSide"
            align="end"
            :side-offset="8"
        >
            <UserMenuContent :user="user" />
        </DropdownMenuContent>
    </DropdownMenu>
</template>
