<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { BookOpen, Calendar, ClipboardList, FolderGit2, Gift, Handshake, LayoutGrid, MapPin, Palette, Puzzle, Rows3, Tag, Ticket, Users } from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupContent,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { index as eventsIndex } from '@/routes/events';
import { index as programsIndex } from '@/routes/programs';
import { index as sponsorLevelsIndex } from '@/routes/sponsor-levels';
import { index as sponsorsIndex } from '@/routes/sponsors';
import { index as usersIndex } from '@/routes/users';
import { index as venuesIndex } from '@/routes/venues';
import { index as ticketTypesIndex } from '@/routes/ticket-types';
import { index as ticketCategoriesIndex } from '@/routes/ticket-categories';
import { index as ticketAddonsIndex } from '@/routes/ticket-addons';
import { index as vouchersIndex } from '@/routes/vouchers';
import { index as ticketsIndex } from '@/routes/tickets';
import type { NavItem } from '@/types';

const page = usePage();

const isAdmin = computed(() => {
    const roles: { name: string }[] = page.props.auth?.user?.roles ?? [];
    return roles.some((role) => role.name === 'admin' || role.name === 'superadmin');
});

const isSponsorManager = computed(() => {
    const roles: { name: string }[] = page.props.auth?.user?.roles ?? [];
    return roles.some((role) => role.name === 'sponsor_manager');
});

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'My Tickets',
        href: ticketsIndex(),
        icon: Ticket,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/lan-software/LanCore.git',
        icon: FolderGit2,
    },
    {
        title: 'Documentation',
        href: 'https://lan-software.de/LanCore/docs',
        icon: BookOpen,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />

            <SidebarGroup v-if="isAdmin">
                <SidebarGroupLabel>Administration</SidebarGroupLabel>
                <SidebarGroupContent>
                    <SidebarMenu>
                        <SidebarMenuItem>
                            <SidebarMenuButton as-child>
                                <Link :href="usersIndex()">
                                    <Users />
                                    <span>Users</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                        <SidebarMenuItem>
                            <SidebarMenuButton as-child>
                                <Link :href="eventsIndex()">
                                    <Calendar />
                                    <span>Events</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                        <SidebarMenuItem>
                            <SidebarMenuButton as-child>
                                <Link :href="programsIndex()">
                                    <ClipboardList />
                                    <span>Programs</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                        <SidebarMenuItem>
                            <SidebarMenuButton as-child>
                                <Link :href="venuesIndex()">
                                    <MapPin />
                                    <span>Venues</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                        <SidebarMenuItem>
                            <SidebarMenuButton as-child>
                                <Link :href="sponsorsIndex()">
                                    <Handshake />
                                    <span>Sponsors</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                        <SidebarMenuItem>
                            <SidebarMenuButton as-child>
                                <Link :href="sponsorLevelsIndex()">
                                    <Palette />
                                    <span>Sponsor Levels</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                        <SidebarMenuItem>
                            <SidebarMenuButton as-child>
                                <Link :href="ticketTypesIndex()">
                                    <Rows3 />
                                    <span>Ticket Types</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                        <SidebarMenuItem>
                            <SidebarMenuButton as-child>
                                <Link :href="ticketCategoriesIndex()">
                                    <Tag />
                                    <span>Ticket Categories</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                        <SidebarMenuItem>
                            <SidebarMenuButton as-child>
                                <Link :href="ticketAddonsIndex()">
                                    <Puzzle />
                                    <span>Ticket Addons</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                        <SidebarMenuItem>
                            <SidebarMenuButton as-child>
                                <Link :href="vouchersIndex()">
                                    <Gift />
                                    <span>Vouchers</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroupContent>
            </SidebarGroup>

            <!-- Sponsor Manager section -->
            <SidebarGroup v-if="!isAdmin && isSponsorManager">
                <SidebarGroupLabel>Sponsor Management</SidebarGroupLabel>
                <SidebarGroupContent>
                    <SidebarMenu>
                        <SidebarMenuItem>
                            <SidebarMenuButton as-child>
                                <Link :href="sponsorsIndex()">
                                    <Handshake />
                                    <span>My Sponsors</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroupContent>
            </SidebarGroup>
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
