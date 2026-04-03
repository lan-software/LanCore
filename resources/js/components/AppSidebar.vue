<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    Calendar,
    ClipboardList,
    Cog,
    CreditCard,
    FileCheck,
    Gamepad2,
    Gift,
    Grid2x2,
    Handshake,
    LayoutGrid,
    MapPin,
    Megaphone,
    MessageSquare,
    Newspaper,
    Palette,
    Pin,
    PinOff,
    Puzzle,
    Rows3,
    ShieldCheck,
    ShoppingCart,
    Tag,
    Ticket,
    TicketCheck,
    Trophy,
    Users,
    Webhook,
} from 'lucide-vue-next';
import { computed } from 'vue';
import { toggle as toggleFavoriteAction } from '@/actions/App/Http/Controllers/Settings/SidebarFavoriteController';
import { usePermissions } from '@/composables/usePermissions';
import AppLogo from '@/components/AppLogo.vue';
import EventSelector from '@/components/EventSelector.vue';
import NavFavorites from '@/components/NavFavorites.vue';
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
    SidebarMenuAction,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard, home } from '@/routes';
import { index as achievementsIndex } from '@/routes/achievements';
import { index as adminTicketsIndex } from '@/routes/admin-tickets';
import { index as announcementsIndex } from '@/routes/announcements';
import { index as eventsIndex } from '@/routes/events';
import { index as gamesIndex } from '@/routes/games';
import { index as globalPurchaseConditionsIndex } from '@/routes/global-purchase-conditions';
import { index as integrationsIndex } from '@/routes/integrations';
import { index as newsIndex } from '@/routes/news';
import { index as newsCommentsIndex } from '@/routes/news/comments';
import { index as myOrdersIndex } from '@/routes/my-orders';
import { index as ordersIndex } from '@/routes/orders';
import { index as paymentProviderConditionsIndex } from '@/routes/payment-provider-conditions';
import { index as programsIndex } from '@/routes/programs';
import { index as purchaseRequirementsIndex } from '@/routes/purchase-requirements';
import { index as seatPlansIndex } from '@/routes/seat-plans';
import { index as sponsorLevelsIndex } from '@/routes/sponsor-levels';
import { index as sponsorsIndex } from '@/routes/sponsors';
import { index as ticketAddonsIndex } from '@/routes/ticket-addons';
import { index as ticketCategoriesIndex } from '@/routes/ticket-categories';
import { index as ticketTypesIndex } from '@/routes/ticket-types';
import { index as ticketsIndex } from '@/routes/tickets';
import { index as usersIndex } from '@/routes/users';
import { index as venuesIndex } from '@/routes/venues';
import { index as vouchersIndex } from '@/routes/vouchers';
import { index as webhooksIndex } from '@/routes/webhooks';
import type { NavItem } from '@/types';

const page = usePage();
const { can, canAny } = usePermissions();

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
    {
        title: 'My Orders',
        href: myOrdersIndex(),
        icon: ShoppingCart,
    },
];

const allPinnableItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [
        {
            id: 'dashboard',
            title: 'Dashboard',
            href: dashboard(),
            icon: LayoutGrid,
        },
        {
            id: 'my-tickets',
            title: 'My Tickets',
            href: ticketsIndex(),
            icon: Ticket,
        },
        {
            id: 'my-orders',
            title: 'My Orders',
            href: myOrdersIndex(),
            icon: ShoppingCart,
        },
    ];

    if (can('manage_users')) {
        items.push({
            id: 'users',
            title: 'Users',
            href: usersIndex(),
            icon: Users,
        });
    }

    if (canAny('manage_news_articles', 'moderate_news_comments')) {
        items.push(
            {
                id: 'news-articles',
                title: 'Articles',
                href: newsIndex(),
                icon: Newspaper,
            },
            {
                id: 'news-comments',
                title: 'Comments',
                href: newsCommentsIndex(),
                icon: MessageSquare,
            },
        );
    }

    if (can('manage_achievements')) {
        items.push({
            id: 'achievements',
            title: 'Achievements',
            href: achievementsIndex(),
            icon: Trophy,
        });
    }

    if (can('manage_announcements')) {
        items.push({
            id: 'announcements',
            title: 'Announcements',
            href: announcementsIndex(),
            icon: Megaphone,
        });
    }

    if (can('manage_events')) {
        items.push({
            id: 'events',
            title: 'Events',
            href: eventsIndex(),
            icon: Calendar,
        });
    }

    if (can('manage_programs')) {
        items.push({
            id: 'programs',
            title: 'Programs',
            href: programsIndex(),
            icon: ClipboardList,
        });
    }

    if (can('manage_venues')) {
        items.push({
            id: 'venues',
            title: 'Venues',
            href: venuesIndex(),
            icon: MapPin,
        });
    }

    if (can('manage_games')) {
        items.push({
            id: 'games',
            title: 'Games',
            href: gamesIndex(),
            icon: Gamepad2,
        });
    }

    if (can('manage_sponsors')) {
        items.push(
            {
                id: 'sponsors',
                title: 'Sponsors',
                href: sponsorsIndex(),
                icon: Handshake,
            },
            {
                id: 'sponsor-levels',
                title: 'Sponsor Levels',
                href: sponsorLevelsIndex(),
                icon: Palette,
            },
        );
    }

    if (!can('manage_sponsors') && can('manage_assigned_sponsors')) {
        items.push({
            id: 'my-sponsors',
            title: 'My Sponsors',
            href: sponsorsIndex(),
            icon: Handshake,
        });
    }

    if (can('manage_sponsor_levels') && !can('manage_sponsors')) {
        items.push({
            id: 'sponsor-levels',
            title: 'Sponsor Levels',
            href: sponsorLevelsIndex(),
            icon: Palette,
        });
    }

    if (can('manage_ticketing')) {
        items.push(
            {
                id: 'ticket-types',
                title: 'Ticket Types',
                href: ticketTypesIndex(),
                icon: Rows3,
            },
            {
                id: 'ticket-categories',
                title: 'Ticket Categories',
                href: ticketCategoriesIndex(),
                icon: Tag,
            },
            {
                id: 'ticket-addons',
                title: 'Ticket Addons',
                href: ticketAddonsIndex(),
                icon: Puzzle,
            },
            {
                id: 'vouchers',
                title: 'Vouchers',
                href: vouchersIndex(),
                icon: Gift,
            },
        );
    }

    if (can('manage_seat_plans')) {
        items.push({
            id: 'seat-plans',
            title: 'Seat Plans',
            href: seatPlansIndex(),
            icon: Grid2x2,
        });
    }

    if (can('manage_webhooks')) {
        items.push({
            id: 'webhooks',
            title: 'Webhooks',
            href: webhooksIndex(),
            icon: Webhook,
        });
    }

    if (can('manage_integrations')) {
        items.push({
            id: 'integrations',
            title: 'Integrations',
            href: integrationsIndex(),
            icon: Cog,
        });
    }

    if (canAny('view_orders', 'manage_orders')) {
        items.push(
            {
                id: 'orders',
                title: 'Orders',
                href: ordersIndex(),
                icon: ShoppingCart,
            },
            {
                id: 'admin-tickets',
                title: 'Tickets (Admin)',
                href: adminTicketsIndex(),
                icon: TicketCheck,
            },
        );
    }

    if (can('manage_shop_conditions')) {
        items.push(
            {
                id: 'purchase-requirements',
                title: 'Purchase Requirements',
                href: purchaseRequirementsIndex(),
                icon: ShieldCheck,
            },
            {
                id: 'purchase-conditions',
                title: 'Purchase Conditions',
                href: globalPurchaseConditionsIndex(),
                icon: FileCheck,
            },
            {
                id: 'payment-conditions',
                title: 'Payment Conditions',
                href: paymentProviderConditionsIndex(),
                icon: CreditCard,
            },
        );
    }

    return items;
});

const sidebarFavorites = computed<string[]>(
    () => page.props.sidebarFavorites ?? [],
);

function isFavorited(itemId: string): boolean {
    return sidebarFavorites.value.includes(itemId);
}

function toggleFavorite(itemId: string): void {
    router.post(
        toggleFavoriteAction().url,
        { item_id: itemId },
        { preserveScroll: true, preserveState: true },
    );
}
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="home()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <EventSelector />

        <SidebarContent>
            <NavMain :items="mainNavItems" />

            <NavFavorites :all-items="allPinnableItems" />

            <!-- Administration -->
            <SidebarGroup v-if="can('manage_users')">
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
                            <SidebarMenuAction
                                :show-on-hover="true"
                                @click="toggleFavorite('users')"
                            >
                                <PinOff
                                    v-if="isFavorited('users')"
                                    class="size-4"
                                />
                                <Pin v-else class="size-4" />
                            </SidebarMenuAction>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroupContent>
            </SidebarGroup>

            <!-- News Domain -->
            <SidebarGroup
                v-if="canAny('manage_news_articles', 'moderate_news_comments')"
            >
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
                            <SidebarMenuAction
                                :show-on-hover="true"
                                @click="toggleFavorite('news-articles')"
                            >
                                <PinOff
                                    v-if="isFavorited('news-articles')"
                                    class="size-4"
                                />
                                <Pin v-else class="size-4" />
                            </SidebarMenuAction>
                        </SidebarMenuItem>
                        <SidebarMenuItem>
                            <SidebarMenuButton as-child>
                                <Link :href="newsCommentsIndex()">
                                    <MessageSquare />
                                    <span>Comments</span>
                                </Link>
                            </SidebarMenuButton>
                            <SidebarMenuAction
                                :show-on-hover="true"
                                @click="toggleFavorite('news-comments')"
                            >
                                <PinOff
                                    v-if="isFavorited('news-comments')"
                                    class="size-4"
                                />
                                <Pin v-else class="size-4" />
                            </SidebarMenuAction>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroupContent>
            </SidebarGroup>

            <!-- Achievements Domain -->
            <SidebarGroup v-if="can('manage_achievements')">
                <SidebarGroupLabel>Achievements</SidebarGroupLabel>
                <SidebarGroupContent>
                    <SidebarMenu>
                        <SidebarMenuItem>
                            <SidebarMenuButton as-child>
                                <Link :href="achievementsIndex()">
                                    <Trophy />
                                    <span>Achievements</span>
                                </Link>
                            </SidebarMenuButton>
                            <SidebarMenuAction
                                :show-on-hover="true"
                                @click="toggleFavorite('achievements')"
                            >
                                <PinOff
                                    v-if="isFavorited('achievements')"
                                    class="size-4"
                                />
                                <Pin v-else class="size-4" />
                            </SidebarMenuAction>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroupContent>
            </SidebarGroup>

            <!-- Announcement Domain -->
            <SidebarGroup v-if="can('manage_announcements')">
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
                            <SidebarMenuAction
                                :show-on-hover="true"
                                @click="toggleFavorite('announcements')"
                            >
                                <PinOff
                                    v-if="isFavorited('announcements')"
                                    class="size-4"
                                />
                                <Pin v-else class="size-4" />
                            </SidebarMenuAction>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroupContent>
            </SidebarGroup>

            <!-- Event Domain -->
            <SidebarGroup v-if="can('manage_events')">
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
                            <SidebarMenuAction
                                :show-on-hover="true"
                                @click="toggleFavorite('events')"
                            >
                                <PinOff
                                    v-if="isFavorited('events')"
                                    class="size-4"
                                />
                                <Pin v-else class="size-4" />
                            </SidebarMenuAction>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroupContent>
            </SidebarGroup>

            <!-- Program Domain -->
            <SidebarGroup v-if="can('manage_programs')">
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
                            <SidebarMenuAction
                                :show-on-hover="true"
                                @click="toggleFavorite('programs')"
                            >
                                <PinOff
                                    v-if="isFavorited('programs')"
                                    class="size-4"
                                />
                                <Pin v-else class="size-4" />
                            </SidebarMenuAction>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroupContent>
            </SidebarGroup>

            <!-- Venue Domain -->
            <SidebarGroup v-if="can('manage_venues')">
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
                            <SidebarMenuAction
                                :show-on-hover="true"
                                @click="toggleFavorite('venues')"
                            >
                                <PinOff
                                    v-if="isFavorited('venues')"
                                    class="size-4"
                                />
                                <Pin v-else class="size-4" />
                            </SidebarMenuAction>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroupContent>
            </SidebarGroup>

            <!-- Games Domain -->
            <SidebarGroup v-if="can('manage_games')">
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
                            <SidebarMenuAction
                                :show-on-hover="true"
                                @click="toggleFavorite('games')"
                            >
                                <PinOff
                                    v-if="isFavorited('games')"
                                    class="size-4"
                                />
                                <Pin v-else class="size-4" />
                            </SidebarMenuAction>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroupContent>
            </SidebarGroup>

            <!-- Sponsoring Domain -->
            <SidebarGroup
                v-if="canAny('manage_sponsors', 'manage_assigned_sponsors')"
            >
                <SidebarGroupLabel>Sponsoring</SidebarGroupLabel>
                <SidebarGroupContent>
                    <SidebarMenu>
                        <SidebarMenuItem v-if="can('manage_sponsors')">
                            <SidebarMenuButton as-child>
                                <Link :href="sponsorsIndex()">
                                    <Handshake />
                                    <span>Sponsors</span>
                                </Link>
                            </SidebarMenuButton>
                            <SidebarMenuAction
                                :show-on-hover="true"
                                @click="toggleFavorite('sponsors')"
                            >
                                <PinOff
                                    v-if="isFavorited('sponsors')"
                                    class="size-4"
                                />
                                <Pin v-else class="size-4" />
                            </SidebarMenuAction>
                        </SidebarMenuItem>
                        <SidebarMenuItem
                            v-if="
                                !can('manage_sponsors') &&
                                can('manage_assigned_sponsors')
                            "
                        >
                            <SidebarMenuButton as-child>
                                <Link :href="sponsorsIndex()">
                                    <Handshake />
                                    <span>My Sponsors</span>
                                </Link>
                            </SidebarMenuButton>
                            <SidebarMenuAction
                                :show-on-hover="true"
                                @click="toggleFavorite('my-sponsors')"
                            >
                                <PinOff
                                    v-if="isFavorited('my-sponsors')"
                                    class="size-4"
                                />
                                <Pin v-else class="size-4" />
                            </SidebarMenuAction>
                        </SidebarMenuItem>
                        <SidebarMenuItem v-if="can('manage_sponsor_levels')">
                            <SidebarMenuButton as-child>
                                <Link :href="sponsorLevelsIndex()">
                                    <Palette />
                                    <span>Sponsor Levels</span>
                                </Link>
                            </SidebarMenuButton>
                            <SidebarMenuAction
                                :show-on-hover="true"
                                @click="toggleFavorite('sponsor-levels')"
                            >
                                <PinOff
                                    v-if="isFavorited('sponsor-levels')"
                                    class="size-4"
                                />
                                <Pin v-else class="size-4" />
                            </SidebarMenuAction>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroupContent>
            </SidebarGroup>

            <!-- Ticketing Domain -->
            <SidebarGroup v-if="can('manage_ticketing')">
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
                            <SidebarMenuAction
                                :show-on-hover="true"
                                @click="toggleFavorite('ticket-types')"
                            >
                                <PinOff
                                    v-if="isFavorited('ticket-types')"
                                    class="size-4"
                                />
                                <Pin v-else class="size-4" />
                            </SidebarMenuAction>
                        </SidebarMenuItem>
                        <SidebarMenuItem>
                            <SidebarMenuButton as-child>
                                <Link :href="ticketCategoriesIndex()">
                                    <Tag />
                                    <span>Ticket Categories</span>
                                </Link>
                            </SidebarMenuButton>
                            <SidebarMenuAction
                                :show-on-hover="true"
                                @click="toggleFavorite('ticket-categories')"
                            >
                                <PinOff
                                    v-if="isFavorited('ticket-categories')"
                                    class="size-4"
                                />
                                <Pin v-else class="size-4" />
                            </SidebarMenuAction>
                        </SidebarMenuItem>
                        <SidebarMenuItem>
                            <SidebarMenuButton as-child>
                                <Link :href="ticketAddonsIndex()">
                                    <Puzzle />
                                    <span>Ticket Addons</span>
                                </Link>
                            </SidebarMenuButton>
                            <SidebarMenuAction
                                :show-on-hover="true"
                                @click="toggleFavorite('ticket-addons')"
                            >
                                <PinOff
                                    v-if="isFavorited('ticket-addons')"
                                    class="size-4"
                                />
                                <Pin v-else class="size-4" />
                            </SidebarMenuAction>
                        </SidebarMenuItem>
                        <SidebarMenuItem>
                            <SidebarMenuButton as-child>
                                <Link :href="vouchersIndex()">
                                    <Gift />
                                    <span>Vouchers</span>
                                </Link>
                            </SidebarMenuButton>
                            <SidebarMenuAction
                                :show-on-hover="true"
                                @click="toggleFavorite('vouchers')"
                            >
                                <PinOff
                                    v-if="isFavorited('vouchers')"
                                    class="size-4"
                                />
                                <Pin v-else class="size-4" />
                            </SidebarMenuAction>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroupContent>
            </SidebarGroup>

            <!-- Competition Domain -->
            <SidebarGroup v-if="can('manage_events')">
                <SidebarGroupLabel>Competition</SidebarGroupLabel>
                <SidebarGroupContent>
                    <SidebarMenu>
                        <SidebarMenuItem>
                            <SidebarMenuButton
                                class="pointer-events-none text-sidebar-foreground/50"
                            >
                                <span>Coming soon</span>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroupContent>
            </SidebarGroup>

            <!-- Orchestration Domain -->
            <SidebarGroup v-if="can('manage_events')">
                <SidebarGroupLabel>Orchestration</SidebarGroupLabel>
                <SidebarGroupContent>
                    <SidebarMenu>
                        <SidebarMenuItem>
                            <SidebarMenuButton
                                class="pointer-events-none text-sidebar-foreground/50"
                            >
                                <span>Coming soon</span>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroupContent>
            </SidebarGroup>

            <!-- Seating Domain -->
            <SidebarGroup v-if="can('manage_seat_plans')">
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
                            <SidebarMenuAction
                                :show-on-hover="true"
                                @click="toggleFavorite('seat-plans')"
                            >
                                <PinOff
                                    v-if="isFavorited('seat-plans')"
                                    class="size-4"
                                />
                                <Pin v-else class="size-4" />
                            </SidebarMenuAction>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroupContent>
            </SidebarGroup>

            <!-- Webhook Domain -->
            <SidebarGroup v-if="can('manage_webhooks')">
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
                            <SidebarMenuAction
                                :show-on-hover="true"
                                @click="toggleFavorite('webhooks')"
                            >
                                <PinOff
                                    v-if="isFavorited('webhooks')"
                                    class="size-4"
                                />
                                <Pin v-else class="size-4" />
                            </SidebarMenuAction>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroupContent>
            </SidebarGroup>

            <!-- Integration Domain -->
            <SidebarGroup v-if="can('manage_integrations')">
                <SidebarGroupLabel>Integrations</SidebarGroupLabel>
                <SidebarGroupContent>
                    <SidebarMenu>
                        <SidebarMenuItem>
                            <SidebarMenuButton as-child>
                                <Link :href="integrationsIndex()">
                                    <Cog />
                                    <span>Integrations</span>
                                </Link>
                            </SidebarMenuButton>
                            <SidebarMenuAction
                                :show-on-hover="true"
                                @click="toggleFavorite('integrations')"
                            >
                                <PinOff
                                    v-if="isFavorited('integrations')"
                                    class="size-4"
                                />
                                <Pin v-else class="size-4" />
                            </SidebarMenuAction>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroupContent>
            </SidebarGroup>

            <!-- Shop Domain -->
            <SidebarGroup
                v-if="
                    canAny(
                        'view_orders',
                        'manage_orders',
                        'manage_vouchers',
                        'manage_shop_conditions',
                    )
                "
            >
                <SidebarGroupLabel>Shop</SidebarGroupLabel>
                <SidebarGroupContent>
                    <SidebarMenu>
                        <SidebarMenuItem>
                            <SidebarMenuButton as-child>
                                <Link :href="ordersIndex()">
                                    <ShoppingCart />
                                    <span>Orders</span>
                                </Link>
                            </SidebarMenuButton>
                            <SidebarMenuAction
                                :show-on-hover="true"
                                @click="toggleFavorite('orders')"
                            >
                                <PinOff
                                    v-if="isFavorited('orders')"
                                    class="size-4"
                                />
                                <Pin v-else class="size-4" />
                            </SidebarMenuAction>
                        </SidebarMenuItem>
                        <SidebarMenuItem>
                            <SidebarMenuButton as-child>
                                <Link :href="adminTicketsIndex()">
                                    <TicketCheck />
                                    <span>Tickets</span>
                                </Link>
                            </SidebarMenuButton>
                            <SidebarMenuAction
                                :show-on-hover="true"
                                @click="toggleFavorite('admin-tickets')"
                            >
                                <PinOff
                                    v-if="isFavorited('admin-tickets')"
                                    class="size-4"
                                />
                                <Pin v-else class="size-4" />
                            </SidebarMenuAction>
                        </SidebarMenuItem>
                        <SidebarMenuItem>
                            <SidebarMenuButton as-child>
                                <Link :href="purchaseRequirementsIndex()">
                                    <ShieldCheck />
                                    <span>Purchase Requirements</span>
                                </Link>
                            </SidebarMenuButton>
                            <SidebarMenuAction
                                :show-on-hover="true"
                                @click="toggleFavorite('purchase-requirements')"
                            >
                                <PinOff
                                    v-if="isFavorited('purchase-requirements')"
                                    class="size-4"
                                />
                                <Pin v-else class="size-4" />
                            </SidebarMenuAction>
                        </SidebarMenuItem>
                        <SidebarMenuItem>
                            <SidebarMenuButton as-child>
                                <Link :href="globalPurchaseConditionsIndex()">
                                    <FileCheck />
                                    <span>Purchase Conditions</span>
                                </Link>
                            </SidebarMenuButton>
                            <SidebarMenuAction
                                :show-on-hover="true"
                                @click="toggleFavorite('purchase-conditions')"
                            >
                                <PinOff
                                    v-if="isFavorited('purchase-conditions')"
                                    class="size-4"
                                />
                                <Pin v-else class="size-4" />
                            </SidebarMenuAction>
                        </SidebarMenuItem>
                        <SidebarMenuItem>
                            <SidebarMenuButton as-child>
                                <Link :href="paymentProviderConditionsIndex()">
                                    <CreditCard />
                                    <span>Payment Conditions</span>
                                </Link>
                            </SidebarMenuButton>
                            <SidebarMenuAction
                                :show-on-hover="true"
                                @click="toggleFavorite('payment-conditions')"
                            >
                                <PinOff
                                    v-if="isFavorited('payment-conditions')"
                                    class="size-4"
                                />
                                <Pin v-else class="size-4" />
                            </SidebarMenuAction>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroupContent>
            </SidebarGroup>
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
