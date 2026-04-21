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
    PlugZap,
    Puzzle,
    Radio,
    Rows3,
    Server,
    ShieldCheck,
    ShoppingCart,
    Swords,
    Tag,
    Ticket,
    TicketCheck,
    Trophy,
    Users,
    Webhook,
} from 'lucide-vue-next';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { toggle as toggleFavoriteAction } from '@/actions/App/Http/Controllers/Settings/SidebarFavoriteController';
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
import { usePermissions } from '@/composables/usePermissions';
import { dashboard, home } from '@/routes';
import { index as achievementsIndex } from '@/routes/achievements';
import { index as adminTeamsIndex } from '@/routes/admin/teams';
import { index as adminTicketsIndex } from '@/routes/admin-tickets';
import { index as announcementsIndex } from '@/routes/announcements';
import { index as competitionsIndex } from '@/routes/competitions';
import { index as eventsIndex } from '@/routes/events';
import { index as externalApisIndex } from '@/routes/external-apis';
import { index as gameServersIndex } from '@/routes/game-servers';
import { index as gamesIndex } from '@/routes/games';
import { index as globalPurchaseConditionsIndex } from '@/routes/global-purchase-conditions';
import { index as integrationsIndex } from '@/routes/integrations';
import { index as myCompetitionsIndex } from '@/routes/my-competitions';
import { index as myOrdersIndex } from '@/routes/my-orders';
import { index as myTeamsIndex } from '@/routes/my-teams';
import { index as newsIndex } from '@/routes/news';
import { index as newsCommentsIndex } from '@/routes/news/comments';
import { index as orchestrationJobsIndex } from '@/routes/orchestration-jobs';
import { index as ordersIndex } from '@/routes/orders';
import { index as organizationSettingsIndex } from '@/routes/organization-settings';
import { index as paymentProviderConditionsIndex } from '@/routes/payment-provider-conditions';
import { index as programsIndex } from '@/routes/programs';
import { index as purchaseRequirementsIndex } from '@/routes/purchase-requirements';
import { index as seatPlansIndex } from '@/routes/seat-plans';
import { index as shopSettingsIndex } from '@/routes/shop-settings';
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
import { Permission } from '@/types';
import type { NavItem } from '@/types';

const page = usePage();
const { can, canAny } = usePermissions();
const { t } = useI18n();

const mainNavItems = computed<NavItem[]>(() => [
    {
        title: t('navigation.dashboard'),
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: t('navigation.myTickets'),
        href: ticketsIndex(),
        icon: Ticket,
    },
    {
        title: t('navigation.myOrders'),
        href: myOrdersIndex(),
        icon: ShoppingCart,
    },
    {
        title: t('navigation.myCompetitions'),
        href: myCompetitionsIndex(),
        icon: Swords,
    },
    {
        title: t('navigation.myTeams'),
        href: myTeamsIndex(),
        icon: Users,
    },
]);

const allPinnableItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [
        {
            id: 'dashboard',
            title: t('navigation.dashboard'),
            href: dashboard(),
            icon: LayoutGrid,
        },
        {
            id: 'my-tickets',
            title: t('navigation.myTickets'),
            href: ticketsIndex(),
            icon: Ticket,
        },
        {
            id: 'my-orders',
            title: t('navigation.myOrders'),
            href: myOrdersIndex(),
            icon: ShoppingCart,
        },
        {
            id: 'my-competitions',
            title: t('navigation.myCompetitions'),
            href: myCompetitionsIndex(),
            icon: Swords,
        },
    ];

    if (can(Permission.ManageUsers)) {
        items.push({
            id: 'users',
            title: t('navigation.users'),
            href: usersIndex(),
            icon: Users,
        });
    }

    if (
        canAny(Permission.ManageNewsArticles, Permission.ModerateNewsComments)
    ) {
        items.push(
            {
                id: 'news-articles',
                title: t('navigation.articles'),
                href: newsIndex(),
                icon: Newspaper,
            },
            {
                id: 'news-comments',
                title: t('navigation.comments'),
                href: newsCommentsIndex(),
                icon: MessageSquare,
            },
        );
    }

    if (can(Permission.ManageAchievements)) {
        items.push({
            id: 'achievements',
            title: t('navigation.achievements'),
            href: achievementsIndex(),
            icon: Trophy,
        });
    }

    if (can(Permission.ManageAnnouncements)) {
        items.push({
            id: 'announcements',
            title: t('navigation.announcements'),
            href: announcementsIndex(),
            icon: Megaphone,
        });
    }

    if (can(Permission.ManageEvents)) {
        items.push({
            id: 'events',
            title: t('navigation.events'),
            href: eventsIndex(),
            icon: Calendar,
        });
    }

    if (can(Permission.ManagePrograms)) {
        items.push({
            id: 'programs',
            title: t('navigation.programs'),
            href: programsIndex(),
            icon: ClipboardList,
        });
    }

    if (can(Permission.ManageVenues)) {
        items.push({
            id: 'venues',
            title: t('navigation.venues'),
            href: venuesIndex(),
            icon: MapPin,
        });
    }

    if (can(Permission.ManageCompetitions)) {
        items.push(
            {
                id: 'competitions',
                title: t('navigation.competitions'),
                href: competitionsIndex(),
                icon: Swords,
            },
            {
                id: 'admin-teams',
                title: t('navigation.teams'),
                href: adminTeamsIndex(),
                icon: Users,
            },
        );
    }

    if (can(Permission.ManageGames)) {
        items.push({
            id: 'games',
            title: t('navigation.games'),
            href: gamesIndex(),
            icon: Gamepad2,
        });
    }

    if (can(Permission.ManageSponsors)) {
        items.push(
            {
                id: 'sponsors',
                title: t('navigation.sponsors'),
                href: sponsorsIndex(),
                icon: Handshake,
            },
            {
                id: 'sponsor-levels',
                title: t('navigation.sponsorLevels'),
                href: sponsorLevelsIndex(),
                icon: Palette,
            },
        );
    }

    if (
        !can(Permission.ManageSponsors) &&
        can(Permission.ManageAssignedSponsors)
    ) {
        items.push({
            id: 'my-sponsors',
            title: t('navigation.mySponsors'),
            href: sponsorsIndex(),
            icon: Handshake,
        });
    }

    if (
        can(Permission.ManageSponsorLevels) &&
        !can(Permission.ManageSponsors)
    ) {
        items.push({
            id: 'sponsor-levels',
            title: t('navigation.sponsorLevels'),
            href: sponsorLevelsIndex(),
            icon: Palette,
        });
    }

    if (can(Permission.ManageTicketing)) {
        items.push(
            {
                id: 'ticket-types',
                title: t('navigation.ticketTypes'),
                href: ticketTypesIndex(),
                icon: Rows3,
            },
            {
                id: 'ticket-categories',
                title: t('navigation.ticketCategories'),
                href: ticketCategoriesIndex(),
                icon: Tag,
            },
            {
                id: 'ticket-addons',
                title: t('navigation.ticketAddons'),
                href: ticketAddonsIndex(),
                icon: Puzzle,
            },
            {
                id: 'vouchers',
                title: t('navigation.vouchers'),
                href: vouchersIndex(),
                icon: Gift,
            },
        );
    }

    if (can(Permission.ManageSeatPlans)) {
        items.push({
            id: 'seat-plans',
            title: t('navigation.seatPlans'),
            href: seatPlansIndex(),
            icon: Grid2x2,
        });
    }

    if (can(Permission.ManageWebhooks)) {
        items.push({
            id: 'webhooks',
            title: t('navigation.webhooks'),
            href: webhooksIndex(),
            icon: Webhook,
        });
    }

    if (can(Permission.ManageIntegrations)) {
        items.push({
            id: 'integrations',
            title: t('navigation.integrations'),
            href: integrationsIndex(),
            icon: Cog,
        });
    }

    if (canAny(Permission.ViewOrders, Permission.ManageOrders)) {
        items.push(
            {
                id: 'orders',
                title: t('navigation.orders'),
                href: ordersIndex(),
                icon: ShoppingCart,
            },
            {
                id: 'admin-tickets',
                title: t('navigation.ticketsAdmin'),
                href: adminTicketsIndex(),
                icon: TicketCheck,
            },
        );
    }

    if (can(Permission.ManageShopConditions)) {
        items.push(
            {
                id: 'purchase-requirements',
                title: t('navigation.purchaseRequirements'),
                href: purchaseRequirementsIndex(),
                icon: ShieldCheck,
            },
            {
                id: 'purchase-conditions',
                title: t('navigation.purchaseConditions'),
                href: globalPurchaseConditionsIndex(),
                icon: FileCheck,
            },
            {
                id: 'payment-conditions',
                title: t('navigation.paymentConditions'),
                href: paymentProviderConditionsIndex(),
                icon: CreditCard,
            },
        );
    }

    if (can(Permission.ManageGameServers)) {
        items.push({
            id: 'game-servers',
            title: t('navigation.gameServers'),
            href: gameServersIndex(),
            icon: Server,
        });
    }

    if (canAny(Permission.ViewOrchestration, Permission.ManageGameServers)) {
        items.push(
            {
                id: 'orchestration-jobs',
                title: t('navigation.orchestration'),
                href: orchestrationJobsIndex(),
                icon: Radio,
            },
            {
                id: 'external-apis',
                title: t('navigation.externalApis'),
                href: externalApisIndex(),
                icon: PlugZap,
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

            <!-- Platform Settings -->
            <SidebarGroup v-if="canAny(Permission.ManageUsers)">
                <SidebarGroupLabel>{{
                    $t('navigation.groups.platform')
                }}</SidebarGroupLabel>
                <SidebarGroupContent>
                    <SidebarMenu>
                        <SidebarMenuItem>
                            <SidebarMenuButton as-child>
                                <Link :href="organizationSettingsIndex()">
                                    <Cog />
                                    <span>{{
                                        $t('navigation.organization')
                                    }}</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroupContent>
            </SidebarGroup>

            <!-- Administration -->
            <SidebarGroup v-if="can(Permission.ManageUsers)">
                <SidebarGroupLabel>{{
                    $t('navigation.groups.administration')
                }}</SidebarGroupLabel>
                <SidebarGroupContent>
                    <SidebarMenu>
                        <SidebarMenuItem>
                            <SidebarMenuButton as-child>
                                <Link :href="usersIndex()">
                                    <Users />
                                    <span>{{ $t('navigation.users') }}</span>
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
                v-if="
                    canAny(
                        Permission.ManageNewsArticles,
                        Permission.ModerateNewsComments,
                    )
                "
            >
                <SidebarGroupLabel>{{
                    $t('navigation.groups.news')
                }}</SidebarGroupLabel>
                <SidebarGroupContent>
                    <SidebarMenu>
                        <SidebarMenuItem>
                            <SidebarMenuButton as-child>
                                <Link :href="newsIndex()">
                                    <Newspaper />
                                    <span>{{
                                        $t('navigation.articles')
                                    }}</span>
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
                                    <span>{{
                                        $t('navigation.comments')
                                    }}</span>
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
            <SidebarGroup v-if="can(Permission.ManageAchievements)">
                <SidebarGroupLabel>{{
                    $t('navigation.groups.achievements')
                }}</SidebarGroupLabel>
                <SidebarGroupContent>
                    <SidebarMenu>
                        <SidebarMenuItem>
                            <SidebarMenuButton as-child>
                                <Link :href="achievementsIndex()">
                                    <Trophy />
                                    <span>{{
                                        $t('navigation.achievements')
                                    }}</span>
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
            <SidebarGroup v-if="can(Permission.ManageAnnouncements)">
                <SidebarGroupLabel>{{
                    $t('navigation.groups.announcement')
                }}</SidebarGroupLabel>
                <SidebarGroupContent>
                    <SidebarMenu>
                        <SidebarMenuItem>
                            <SidebarMenuButton as-child>
                                <Link :href="announcementsIndex()">
                                    <Megaphone />
                                    <span>{{
                                        $t('navigation.announcements')
                                    }}</span>
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
            <SidebarGroup v-if="can(Permission.ManageEvents)">
                <SidebarGroupLabel>{{
                    $t('navigation.groups.event')
                }}</SidebarGroupLabel>
                <SidebarGroupContent>
                    <SidebarMenu>
                        <SidebarMenuItem>
                            <SidebarMenuButton as-child>
                                <Link :href="eventsIndex()">
                                    <Calendar />
                                    <span>{{ $t('navigation.events') }}</span>
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
            <SidebarGroup v-if="can(Permission.ManagePrograms)">
                <SidebarGroupLabel>{{
                    $t('navigation.groups.program')
                }}</SidebarGroupLabel>
                <SidebarGroupContent>
                    <SidebarMenu>
                        <SidebarMenuItem>
                            <SidebarMenuButton as-child>
                                <Link :href="programsIndex()">
                                    <ClipboardList />
                                    <span>{{
                                        $t('navigation.programs')
                                    }}</span>
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
            <SidebarGroup v-if="can(Permission.ManageVenues)">
                <SidebarGroupLabel>{{
                    $t('navigation.groups.venue')
                }}</SidebarGroupLabel>
                <SidebarGroupContent>
                    <SidebarMenu>
                        <SidebarMenuItem>
                            <SidebarMenuButton as-child>
                                <Link :href="venuesIndex()">
                                    <MapPin />
                                    <span>{{ $t('navigation.venues') }}</span>
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
            <SidebarGroup v-if="can(Permission.ManageGames)">
                <SidebarGroupLabel>{{
                    $t('navigation.groups.games')
                }}</SidebarGroupLabel>
                <SidebarGroupContent>
                    <SidebarMenu>
                        <SidebarMenuItem>
                            <SidebarMenuButton as-child>
                                <Link :href="gamesIndex()">
                                    <Gamepad2 />
                                    <span>{{ $t('navigation.games') }}</span>
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
                v-if="
                    canAny(
                        Permission.ManageSponsors,
                        Permission.ManageAssignedSponsors,
                    )
                "
            >
                <SidebarGroupLabel>{{
                    $t('navigation.groups.sponsoring')
                }}</SidebarGroupLabel>
                <SidebarGroupContent>
                    <SidebarMenu>
                        <SidebarMenuItem v-if="can(Permission.ManageSponsors)">
                            <SidebarMenuButton as-child>
                                <Link :href="sponsorsIndex()">
                                    <Handshake />
                                    <span>{{
                                        $t('navigation.sponsors')
                                    }}</span>
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
                                !can(Permission.ManageSponsors) &&
                                can(Permission.ManageAssignedSponsors)
                            "
                        >
                            <SidebarMenuButton as-child>
                                <Link :href="sponsorsIndex()">
                                    <Handshake />
                                    <span>{{
                                        $t('navigation.mySponsors')
                                    }}</span>
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
                        <SidebarMenuItem
                            v-if="can(Permission.ManageSponsorLevels)"
                        >
                            <SidebarMenuButton as-child>
                                <Link :href="sponsorLevelsIndex()">
                                    <Palette />
                                    <span>{{
                                        $t('navigation.sponsorLevels')
                                    }}</span>
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
            <SidebarGroup v-if="can(Permission.ManageTicketing)">
                <SidebarGroupLabel>{{
                    $t('navigation.groups.ticketing')
                }}</SidebarGroupLabel>
                <SidebarGroupContent>
                    <SidebarMenu>
                        <SidebarMenuItem>
                            <SidebarMenuButton as-child>
                                <Link :href="ticketTypesIndex()">
                                    <Rows3 />
                                    <span>{{
                                        $t('navigation.ticketTypes')
                                    }}</span>
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
                                    <span>{{
                                        $t('navigation.ticketCategories')
                                    }}</span>
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
                                    <span>{{
                                        $t('navigation.ticketAddons')
                                    }}</span>
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
                                    <span>{{
                                        $t('navigation.vouchers')
                                    }}</span>
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
            <SidebarGroup v-if="can(Permission.ManageCompetitions)">
                <SidebarGroupLabel>{{
                    $t('navigation.groups.competition')
                }}</SidebarGroupLabel>
                <SidebarGroupContent>
                    <SidebarMenu>
                        <SidebarMenuItem>
                            <SidebarMenuButton as-child>
                                <Link :href="competitionsIndex()">
                                    <Swords />
                                    <span>{{
                                        $t('navigation.competitions')
                                    }}</span>
                                </Link>
                            </SidebarMenuButton>
                            <SidebarMenuAction
                                :show-on-hover="true"
                                @click="toggleFavorite('competitions')"
                            >
                                <PinOff
                                    v-if="isFavorited('competitions')"
                                    class="size-4"
                                />
                                <Pin v-else class="size-4" />
                            </SidebarMenuAction>
                        </SidebarMenuItem>
                        <SidebarMenuItem>
                            <SidebarMenuButton as-child>
                                <Link :href="adminTeamsIndex()">
                                    <Users />
                                    <span>{{ $t('navigation.teams') }}</span>
                                </Link>
                            </SidebarMenuButton>
                            <SidebarMenuAction
                                :show-on-hover="true"
                                @click="toggleFavorite('admin-teams')"
                            >
                                <PinOff
                                    v-if="isFavorited('admin-teams')"
                                    class="size-4"
                                />
                                <Pin v-else class="size-4" />
                            </SidebarMenuAction>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroupContent>
            </SidebarGroup>

            <!-- Orchestration Domain -->
            <SidebarGroup
                v-if="
                    canAny(
                        Permission.ManageGameServers,
                        Permission.ViewOrchestration,
                    )
                "
            >
                <SidebarGroupLabel>{{
                    $t('navigation.groups.orchestration')
                }}</SidebarGroupLabel>
                <SidebarGroupContent>
                    <SidebarMenu>
                        <SidebarMenuItem
                            v-if="can(Permission.ManageGameServers)"
                        >
                            <SidebarMenuButton as-child>
                                <Link :href="gameServersIndex()">
                                    <Server />
                                    <span>{{
                                        $t('navigation.gameServers')
                                    }}</span>
                                </Link>
                            </SidebarMenuButton>
                            <SidebarMenuAction
                                :show-on-hover="true"
                                @click="toggleFavorite('game-servers')"
                            >
                                <PinOff
                                    v-if="isFavorited('game-servers')"
                                    class="size-4"
                                />
                                <Pin v-else class="size-4" />
                            </SidebarMenuAction>
                        </SidebarMenuItem>
                        <SidebarMenuItem>
                            <SidebarMenuButton as-child>
                                <Link :href="orchestrationJobsIndex()">
                                    <Radio />
                                    <span>{{
                                        $t('navigation.orchestration')
                                    }}</span>
                                </Link>
                            </SidebarMenuButton>
                            <SidebarMenuAction
                                :show-on-hover="true"
                                @click="toggleFavorite('orchestration-jobs')"
                            >
                                <PinOff
                                    v-if="isFavorited('orchestration-jobs')"
                                    class="size-4"
                                />
                                <Pin v-else class="size-4" />
                            </SidebarMenuAction>
                        </SidebarMenuItem>
                        <SidebarMenuItem>
                            <SidebarMenuButton as-child>
                                <Link :href="externalApisIndex()">
                                    <PlugZap />
                                    <span>{{
                                        $t('navigation.externalApis')
                                    }}</span>
                                </Link>
                            </SidebarMenuButton>
                            <SidebarMenuAction
                                :show-on-hover="true"
                                @click="toggleFavorite('external-apis')"
                            >
                                <PinOff
                                    v-if="isFavorited('external-apis')"
                                    class="size-4"
                                />
                                <Pin v-else class="size-4" />
                            </SidebarMenuAction>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroupContent>
            </SidebarGroup>

            <!-- Seating Domain -->
            <SidebarGroup v-if="can(Permission.ManageSeatPlans)">
                <SidebarGroupLabel>{{
                    $t('navigation.groups.seating')
                }}</SidebarGroupLabel>
                <SidebarGroupContent>
                    <SidebarMenu>
                        <SidebarMenuItem>
                            <SidebarMenuButton as-child>
                                <Link :href="seatPlansIndex()">
                                    <Grid2x2 />
                                    <span>{{
                                        $t('navigation.seatPlans')
                                    }}</span>
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
            <SidebarGroup v-if="can(Permission.ManageWebhooks)">
                <SidebarGroupLabel>{{
                    $t('navigation.groups.webhooks')
                }}</SidebarGroupLabel>
                <SidebarGroupContent>
                    <SidebarMenu>
                        <SidebarMenuItem>
                            <SidebarMenuButton as-child>
                                <Link :href="webhooksIndex()">
                                    <Webhook />
                                    <span>{{
                                        $t('navigation.webhooks')
                                    }}</span>
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
            <SidebarGroup v-if="can(Permission.ManageIntegrations)">
                <SidebarGroupLabel>{{
                    $t('navigation.groups.integrations')
                }}</SidebarGroupLabel>
                <SidebarGroupContent>
                    <SidebarMenu>
                        <SidebarMenuItem>
                            <SidebarMenuButton as-child>
                                <Link :href="integrationsIndex()">
                                    <Cog />
                                    <span>{{
                                        $t('navigation.integrations')
                                    }}</span>
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
                        Permission.ViewOrders,
                        Permission.ManageOrders,
                        Permission.ManageVouchers,
                        Permission.ManageShopConditions,
                    )
                "
            >
                <SidebarGroupLabel>{{
                    $t('navigation.groups.shop')
                }}</SidebarGroupLabel>
                <SidebarGroupContent>
                    <SidebarMenu>
                        <SidebarMenuItem>
                            <SidebarMenuButton as-child>
                                <Link :href="shopSettingsIndex()">
                                    <Cog />
                                    <span>{{
                                        $t('navigation.settings')
                                    }}</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                        <SidebarMenuItem>
                            <SidebarMenuButton as-child>
                                <Link :href="ordersIndex()">
                                    <ShoppingCart />
                                    <span>{{ $t('navigation.orders') }}</span>
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
                                    <span>{{
                                        $t('navigation.ticketsAdmin')
                                    }}</span>
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
                                    <span>{{
                                        $t('navigation.purchaseRequirements')
                                    }}</span>
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
                                    <span>{{
                                        $t('navigation.purchaseConditions')
                                    }}</span>
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
                                    <span>{{
                                        $t('navigation.paymentConditions')
                                    }}</span>
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
