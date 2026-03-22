<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { BookOpen, Calendar, ClipboardList, FolderGit2, Gamepad2, Gift, Grid2x2, Handshake, LayoutGrid, MapPin, Megaphone, MessageSquare, Newspaper, Palette, Puzzle, Rows3, Tag, Ticket, Users, Webhook } from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import EventSelector from '@/components/EventSelector.vue';
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
import { index as gamesIndex } from '@/routes/games';
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
import { index as seatPlansIndex } from '@/routes/seat-plans';
import { index as announcementsIndex } from '@/routes/announcements';
import { index as webhooksIndex } from '@/routes/webhooks';
import { index as newsIndex } from '@/routes/news';
import { index as newsCommentsIndex } from '@/routes/news/comments';
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

        <EventSelector />

        <SidebarContent>
            <NavMain :items="mainNavItems" />

            <!-- Administration -->
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
                    </SidebarMenu>
                </SidebarGroupContent>
            </SidebarGroup>

            <!-- News Domain -->
            <SidebarGroup v-if="isAdmin">
                <SidebarGroupLabel>News</SidebarGroupLabel>
                <SidebarGroupContent>
                    <SidebarMenu>
                        <SidebarMenuItem>
                            <SidebarMenuButton as-child>
                                <Link :href="newsIndex()">
                                    <Newspaper />
                                    <span>Articles</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                        <SidebarMenuItem>
                            <SidebarMenuButton as-child>
                                <Link :href="newsCommentsIndex()">
                                    <MessageSquare />
                                    <span>Comments</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroupContent>
            </SidebarGroup>

            <!-- Announcement Domain -->
            <SidebarGroup v-if="isAdmin">
                <SidebarGroupLabel>Announcement</SidebarGroupLabel>
                <SidebarGroupContent>
                    <SidebarMenu>
                        <SidebarMenuItem>
                            <SidebarMenuButton as-child>
                                <Link :href="announcementsIndex()">
                                    <Megaphone />
                                    <span>Announcements</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroupContent>
            </SidebarGroup>

            <!-- Event Domain -->
            <SidebarGroup v-if="isAdmin">
                <SidebarGroupLabel>Event</SidebarGroupLabel>
                <SidebarGroupContent>
                    <SidebarMenu>
                        <SidebarMenuItem>
                            <SidebarMenuButton as-child>
                                <Link :href="eventsIndex()">
                                    <Calendar />
                                    <span>Events</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroupContent>
            </SidebarGroup>

            <!-- Program Domain -->
            <SidebarGroup v-if="isAdmin">
                <SidebarGroupLabel>Program</SidebarGroupLabel>
                <SidebarGroupContent>
                    <SidebarMenu>
                        <SidebarMenuItem>
                            <SidebarMenuButton as-child>
                                <Link :href="programsIndex()">
                                    <ClipboardList />
                                    <span>Programs</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroupContent>
            </SidebarGroup>

            <!-- Venue Domain -->
            <SidebarGroup v-if="isAdmin">
                <SidebarGroupLabel>Venue</SidebarGroupLabel>
                <SidebarGroupContent>
                    <SidebarMenu>
                        <SidebarMenuItem>
                            <SidebarMenuButton as-child>
                                <Link :href="venuesIndex()">
                                    <MapPin />
                                    <span>Venues</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroupContent>
            </SidebarGroup>

            <!-- Games Domain -->
            <SidebarGroup v-if="isAdmin">
                <SidebarGroupLabel>Games</SidebarGroupLabel>
                <SidebarGroupContent>
                    <SidebarMenu>
                        <SidebarMenuItem>
                            <SidebarMenuButton as-child>
                                <Link :href="gamesIndex()">
                                    <Gamepad2 />
                                    <span>Games</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroupContent>
            </SidebarGroup>

            <!-- Sponsoring Domain -->
            <SidebarGroup v-if="isAdmin || isSponsorManager">
                <SidebarGroupLabel>Sponsoring</SidebarGroupLabel>
                <SidebarGroupContent>
                    <SidebarMenu>
                        <SidebarMenuItem v-if="isAdmin">
                            <SidebarMenuButton as-child>
                                <Link :href="sponsorsIndex()">
                                    <Handshake />
                                    <span>Sponsors</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                        <SidebarMenuItem v-if="!isAdmin && isSponsorManager">
                            <SidebarMenuButton as-child>
                                <Link :href="sponsorsIndex()">
                                    <Handshake />
                                    <span>My Sponsors</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                        <SidebarMenuItem v-if="isAdmin">
                            <SidebarMenuButton as-child>
                                <Link :href="sponsorLevelsIndex()">
                                    <Palette />
                                    <span>Sponsor Levels</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroupContent>
            </SidebarGroup>

            <!-- Ticketing Domain -->
            <SidebarGroup v-if="isAdmin">
                <SidebarGroupLabel>Ticketing</SidebarGroupLabel>
                <SidebarGroupContent>
                    <SidebarMenu>
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

            <!-- Competition Domain -->
            <SidebarGroup v-if="isAdmin">
                <SidebarGroupLabel>Competition</SidebarGroupLabel>
                <SidebarGroupContent>
                    <SidebarMenu>
                        <SidebarMenuItem>
                            <SidebarMenuButton class="text-sidebar-foreground/50 pointer-events-none">
                                <span>Coming soon</span>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroupContent>
            </SidebarGroup>

            <!-- Orchestration Domain -->
            <SidebarGroup v-if="isAdmin">
                <SidebarGroupLabel>Orchestration</SidebarGroupLabel>
                <SidebarGroupContent>
                    <SidebarMenu>
                        <SidebarMenuItem>
                            <SidebarMenuButton class="text-sidebar-foreground/50 pointer-events-none">
                                <span>Coming soon</span>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroupContent>
            </SidebarGroup>

            <!-- Seating Domain -->
            <SidebarGroup v-if="isAdmin">
                <SidebarGroupLabel>Seating</SidebarGroupLabel>
                <SidebarGroupContent>
                    <SidebarMenu>
                        <SidebarMenuItem>
                            <SidebarMenuButton as-child>
                                <Link :href="seatPlansIndex()">
                                    <Grid2x2 />
                                    <span>Seat Plans</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroupContent>
            </SidebarGroup>

            <!-- Webhook Domain -->
            <SidebarGroup v-if="isAdmin">
                <SidebarGroupLabel>Webhooks</SidebarGroupLabel>
                <SidebarGroupContent>
                    <SidebarMenu>
                        <SidebarMenuItem>
                            <SidebarMenuButton as-child>
                                <Link :href="webhooksIndex()">
                                    <Webhook />
                                    <span>Webhooks</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroupContent>
            </SidebarGroup>

            <!-- Shop Domain -->
            <SidebarGroup v-if="isAdmin">
                <SidebarGroupLabel>Shop</SidebarGroupLabel>
                <SidebarGroupContent>
                    <SidebarMenu>
                        <SidebarMenuItem>
                            <SidebarMenuButton class="text-sidebar-foreground/50 pointer-events-none">
                                <span>Coming soon</span>
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
