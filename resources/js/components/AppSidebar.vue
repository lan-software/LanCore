<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    Activity,
    Calendar,
    ChartGantt,
    ClipboardList,
    Cog,
    CreditCard,
    FileCheck,
    FileText,
    Gamepad2,
    Gauge,
    GaugeCircle,
    Gift,
    Grid2x2,
    Handshake,
    History,
    LayoutGrid,
    Mail,
    MailPlus,
    MapPin,
    Megaphone,
    MessageSquare,
    Newspaper,
    PaintBucket,
    Palette,
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
    Timer,
    Trash2,
    Trophy,
    Users,
    UsersRound,
    Webhook,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import AppLogo from '@/components/AppLogo.vue';
import CollapsibleSidebarGroup from '@/components/CollapsibleSidebarGroup.vue';
import EventSelector from '@/components/EventSelector.vue';
import NavFavorites from '@/components/NavFavorites.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import SidebarLink from '@/components/SidebarLink.vue';
import SidebarSearch from '@/components/SidebarSearch.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { usePermissions } from '@/composables/usePermissions';
import { dashboard, home } from '@/routes';
import { index as achievementsIndex } from '@/routes/achievements';
import dataLifecycleRoutes from '@/routes/admin/data-lifecycle';
import { index as adminEmailsIndex } from '@/routes/admin/emails';
import { index as adminTeamsIndex } from '@/routes/admin/teams';
import { index as adminTicketsIndex } from '@/routes/admin-tickets';
import { index as announcementsIndex } from '@/routes/announcements';
import { index as competitionsIndex } from '@/routes/competitions';
import {
    dashboard as eventsDashboard,
    index as eventsIndex,
} from '@/routes/events';
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
import { index as newsletterListsIndex } from '@/routes/newsletter-lists';
import { index as orchestrationJobsIndex } from '@/routes/orchestration-jobs';
import { index as ordersIndex } from '@/routes/orders';
import { index as orgaTeamsIndex } from '@/routes/orga-teams';
import { index as organizationSettingsIndex } from '@/routes/organization-settings';
import { index as paymentProviderConditionsIndex } from '@/routes/payment-provider-conditions';
import { index as programsIndex } from '@/routes/programs';
import { index as purchaseRequirementsIndex } from '@/routes/purchase-requirements';
import { index as seatPlansIndex } from '@/routes/seat-plans';
import { index as shopSettingsIndex } from '@/routes/shop-settings';
import { index as sponsorLevelsIndex } from '@/routes/sponsor-levels';
import { index as sponsorsIndex } from '@/routes/sponsors';
import { index as themesIndex } from '@/routes/themes';
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

const search = ref<string>('');

const isSuperadmin = computed<boolean>(() => {
    const roles =
        (
            page.props.auth as
                | { user?: { roles?: { name: string }[] } }
                | undefined
        )?.user?.roles ?? [];

    return roles.some((role) => role.name === 'superadmin');
});

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
        title: t('navigation.playNext'),
        href: '/portal/play-next',
        icon: ChartGantt,
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
            id: 'organization',
            title: t('navigation.organization'),
            href: organizationSettingsIndex(),
            icon: Cog,
        });
        items.push({
            id: 'users',
            title: t('navigation.users'),
            href: usersIndex(),
            icon: Users,
        });
    }

    if (can(Permission.ManagePolicies)) {
        items.push({
            id: 'policies',
            title: t('navigation.policies'),
            href: { url: '/backstage/policies', method: 'get' as const },
            icon: FileText,
        });
    }

    if (can(Permission.ViewEmailLog)) {
        items.push({
            id: 'emails',
            title: 'Emails',
            href: adminEmailsIndex(),
            icon: Mail,
        });
    }

    if (can(Permission.ManageNewsletterLists)) {
        items.push({
            id: 'newsletter-lists',
            title: 'Newsletter Lists',
            href: newsletterListsIndex(),
            icon: MailPlus,
        });
    }

    if (can(Permission.ManageThemes)) {
        items.push({
            id: 'themes',
            title: 'Themes',
            href: themesIndex(),
            icon: PaintBucket,
        });
    }

    if (canAny(Permission.ManageGameServers, Permission.ViewOrchestration)) {
        items.push({
            id: 'external-apis',
            title: t('navigation.externalApis'),
            href: externalApisIndex(),
            icon: PlugZap,
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
        items.push({
            id: 'event-dashboard',
            title: t('navigation.eventDashboard'),
            href: eventsDashboard(),
            icon: Gauge,
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
            // Session-scoped via Event Selector (selected_event_id), per spec.
            {
                id: 'competition-board',
                title: t('navigation.competitionBoard'),
                href: '/backstage/competition-board',
                icon: ChartGantt,
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

    if (can(Permission.ManageOrgaTeams)) {
        items.push({
            id: 'orga-teams',
            title: 'Orga-Teams',
            href: orgaTeamsIndex(),
            icon: UsersRound,
        });
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
            id: 'sponsor-levels-only',
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
                id: 'shop-settings',
                title: t('navigation.settings'),
                href: shopSettingsIndex(),
                icon: Cog,
            },
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
        items.push({
            id: 'orchestration-jobs',
            title: t('navigation.orchestration'),
            href: orchestrationJobsIndex(),
            icon: Radio,
        });
    }

    if (can('view_deletion_requests')) {
        items.push({
            id: 'deletion-requests',
            title: 'Deletion requests',
            href: dataLifecycleRoutes.deletionRequests.index(),
            icon: Trash2,
        });
        items.push({
            id: 'anonymization-log',
            title: 'Anonymization log',
            href: dataLifecycleRoutes.anonymizationLog.index(),
            icon: History,
        });
    }

    if (can('manage_retention_policies')) {
        items.push({
            id: 'retention-policies',
            title: 'Retention policies',
            href: dataLifecycleRoutes.retentionPolicies.index(),
            icon: Timer,
        });
    }

    if (isSuperadmin.value) {
        items.push({
            id: 'horizon',
            title: 'Queue Monitor',
            href: { url: '/horizon', method: 'get' as const },
            icon: GaugeCircle,
        });
        items.push({
            id: 'pulse',
            title: 'Pulse',
            href: { url: '/pulse', method: 'get' as const },
            icon: Activity,
        });
    }

    return items;
});

// Helpers for visibility predicates that mirror the prior hardcoded outer
// `v-if` on each SidebarGroup. Keeps the template terse.
const showPlatform = computed(
    () =>
        isSuperadmin.value ||
        canAny(
            Permission.ManageUsers,
            Permission.ManagePolicies,
            Permission.ViewEmailLog,
            Permission.ManageNewsletterLists,
            Permission.ManageThemes,
            Permission.ManageGameServers,
            Permission.ViewOrchestration,
        ),
);
const showAdministration = computed(() =>
    canAny(Permission.ManageUsers, Permission.ManageAchievements),
);
const showDataLifecycle = computed(() =>
    canAny('view_deletion_requests', 'manage_retention_policies'),
);
const showNews = computed(() =>
    canAny(Permission.ManageNewsArticles, Permission.ModerateNewsComments),
);
const showAnnouncements = computed(() => can(Permission.ManageAnnouncements));
const showEvents = computed(() =>
    canAny(
        Permission.ManageEvents,
        Permission.ManageOrgaTeams,
        Permission.ManagePrograms,
        Permission.ManageVenues,
        Permission.ManageSeatPlans,
    ),
);
const showGames = computed(() => can(Permission.ManageGames));
const showSponsoring = computed(() =>
    canAny(Permission.ManageSponsors, Permission.ManageAssignedSponsors),
);
const showTicketing = computed(() => can(Permission.ManageTicketing));
const showCompetition = computed(() => can(Permission.ManageCompetitions));
const showOrchestration = computed(() =>
    canAny(Permission.ManageGameServers, Permission.ViewOrchestration),
);
const showIntegrations = computed(() =>
    canAny(Permission.ManageIntegrations, Permission.ManageWebhooks),
);
const showShop = computed(() =>
    canAny(
        Permission.ViewOrders,
        Permission.ManageOrders,
        Permission.ManageVouchers,
        Permission.ManageShopConditions,
    ),
);

// Each group exposes the labels of its rendered items so
// CollapsibleSidebarGroup can decide whether the search query matches.
const labels = {
    platform: computed(() => {
        const out: string[] = [];

        if (can(Permission.ManageUsers)) {
            out.push(t('navigation.organization'));
        }

        if (can(Permission.ManagePolicies)) {
            out.push(t('navigation.policies'));
        }

        if (can(Permission.ViewEmailLog)) {
            out.push('Emails');
        }

        if (can(Permission.ManageNewsletterLists)) {
            out.push('Newsletter Lists');
        }

        if (can(Permission.ManageThemes)) {
            out.push('Themes');
        }

        if (
            canAny(Permission.ManageGameServers, Permission.ViewOrchestration)
        ) {
            out.push(t('navigation.externalApis'));
        }

        if (isSuperadmin.value) {
            out.push('Queue Monitor', 'Pulse');
        }

        return out;
    }),
    administration: computed(() => {
        const out: string[] = [];

        if (can(Permission.ManageUsers)) {
            out.push(t('navigation.users'));
        }

        if (can(Permission.ManageAchievements)) {
            out.push(t('navigation.achievements'));
        }

        return out;
    }),
    dataLifecycle: computed(() => {
        const out: string[] = [];

        if (can('view_deletion_requests')) {
            out.push('Deletion requests', 'Anonymization log');
        }

        if (can('manage_retention_policies')) {
            out.push('Retention policies');
        }

        return out;
    }),
    news: computed(() => [t('navigation.articles'), t('navigation.comments')]),
    announcements: computed(() => [t('navigation.announcements')]),
    events: computed(() => {
        const out: string[] = [];

        if (can(Permission.ManageEvents)) {
            out.push(t('navigation.events'), t('navigation.eventDashboard'));
        }

        if (can(Permission.ManagePrograms)) {
            out.push(t('navigation.programs'));
        }

        if (can(Permission.ManageVenues)) {
            out.push(t('navigation.venues'));
        }

        if (can(Permission.ManageSeatPlans)) {
            out.push(t('navigation.seatPlans'));
        }

        if (can(Permission.ManageOrgaTeams)) {
            out.push('Orga-Teams');
        }

        return out;
    }),
    games: computed(() => [t('navigation.games')]),
    sponsoring: computed(() => {
        const out: string[] = [];

        if (can(Permission.ManageSponsors)) {
            out.push(t('navigation.sponsors'));
        }

        if (
            !can(Permission.ManageSponsors) &&
            can(Permission.ManageAssignedSponsors)
        ) {
            out.push(t('navigation.mySponsors'));
        }

        if (can(Permission.ManageSponsorLevels)) {
            out.push(t('navigation.sponsorLevels'));
        }

        return out;
    }),
    ticketing: computed(() => [
        t('navigation.ticketTypes'),
        t('navigation.ticketCategories'),
        t('navigation.ticketAddons'),
        t('navigation.vouchers'),
    ]),
    competition: computed(() => [
        t('navigation.competitions'),
        t('navigation.teams'),
        t('navigation.competitionBoard'),
    ]),
    orchestration: computed(() => {
        const out: string[] = [];

        if (can(Permission.ManageGameServers)) {
            out.push(t('navigation.gameServers'));
        }

        out.push(t('navigation.orchestration'));

        return out;
    }),
    integrations: computed(() => {
        const out: string[] = [];

        if (can(Permission.ManageIntegrations)) {
            out.push(t('navigation.lanApps'));
        }

        if (can(Permission.ManageWebhooks)) {
            out.push(t('navigation.webhooks'));
        }

        return out;
    }),
    shop: computed(() => [
        t('navigation.settings'),
        t('navigation.orders'),
        t('navigation.ticketsAdmin'),
        t('navigation.purchaseRequirements'),
        t('navigation.purchaseConditions'),
        t('navigation.paymentConditions'),
    ]),
};
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
        <SidebarSearch v-model="search" />

        <SidebarContent>
            <NavMain :items="mainNavItems" />

            <NavFavorites :all-items="allPinnableItems" />

            <CollapsibleSidebarGroup
                group-id="platform"
                :label="t('navigation.groups.platform')"
                :item-labels="labels.platform.value"
                :visible="showPlatform"
                :search-query="search"
            >
                <SidebarMenu>
                    <SidebarLink
                        v-if="can(Permission.ManageUsers)"
                        favorite-id="organization"
                        :label="t('navigation.organization')"
                        :icon="Cog"
                        :href="organizationSettingsIndex().url"
                        :search-query="search"
                    />
                    <SidebarLink
                        v-if="can(Permission.ManagePolicies)"
                        favorite-id="policies"
                        :label="t('navigation.policies')"
                        :icon="FileText"
                        href="/backstage/policies"
                        :search-query="search"
                    />
                    <SidebarLink
                        v-if="can(Permission.ViewEmailLog)"
                        favorite-id="emails"
                        label="Emails"
                        :icon="Mail"
                        :href="adminEmailsIndex().url"
                        :search-query="search"
                    />
                    <SidebarLink
                        v-if="can(Permission.ManageNewsletterLists)"
                        favorite-id="newsletter-lists"
                        label="Newsletter Lists"
                        :icon="MailPlus"
                        :href="newsletterListsIndex().url"
                        :search-query="search"
                    />
                    <SidebarLink
                        v-if="can(Permission.ManageThemes)"
                        favorite-id="themes"
                        label="Themes"
                        :icon="PaintBucket"
                        :href="themesIndex().url"
                        :search-query="search"
                    />
                    <SidebarLink
                        v-if="
                            canAny(
                                Permission.ManageGameServers,
                                Permission.ViewOrchestration,
                            )
                        "
                        favorite-id="external-apis"
                        :label="t('navigation.externalApis')"
                        :icon="PlugZap"
                        :href="externalApisIndex().url"
                        :search-query="search"
                    />
                    <SidebarLink
                        v-if="isSuperadmin"
                        favorite-id="horizon"
                        label="Queue Monitor"
                        :icon="GaugeCircle"
                        external-href="/horizon"
                        :search-query="search"
                    />
                    <SidebarLink
                        v-if="isSuperadmin"
                        favorite-id="pulse"
                        label="Pulse"
                        :icon="Activity"
                        external-href="/pulse"
                        :search-query="search"
                    />
                </SidebarMenu>
            </CollapsibleSidebarGroup>

            <CollapsibleSidebarGroup
                group-id="administration"
                :label="t('navigation.groups.administration')"
                :item-labels="labels.administration.value"
                :visible="showAdministration"
                :search-query="search"
            >
                <SidebarMenu>
                    <SidebarLink
                        v-if="can(Permission.ManageUsers)"
                        favorite-id="users"
                        :label="t('navigation.users')"
                        :icon="Users"
                        :href="usersIndex().url"
                        :search-query="search"
                    />
                    <SidebarLink
                        v-if="can(Permission.ManageAchievements)"
                        favorite-id="achievements"
                        :label="t('navigation.achievements')"
                        :icon="Trophy"
                        :href="achievementsIndex().url"
                        :search-query="search"
                    />
                </SidebarMenu>
            </CollapsibleSidebarGroup>

            <CollapsibleSidebarGroup
                group-id="data-lifecycle"
                :label="t('navigation.groups.dataLifecycle')"
                :item-labels="labels.dataLifecycle.value"
                :visible="showDataLifecycle"
                :search-query="search"
            >
                <SidebarMenu>
                    <SidebarLink
                        v-if="can('view_deletion_requests')"
                        favorite-id="deletion-requests"
                        label="Deletion requests"
                        :icon="Trash2"
                        :href="dataLifecycleRoutes.deletionRequests.index().url"
                        :search-query="search"
                    />
                    <SidebarLink
                        v-if="can('manage_retention_policies')"
                        favorite-id="retention-policies"
                        label="Retention policies"
                        :icon="Timer"
                        :href="
                            dataLifecycleRoutes.retentionPolicies.index().url
                        "
                        :search-query="search"
                    />
                    <SidebarLink
                        v-if="can('view_deletion_requests')"
                        favorite-id="anonymization-log"
                        label="Anonymization log"
                        :icon="History"
                        :href="dataLifecycleRoutes.anonymizationLog.index().url"
                        :search-query="search"
                    />
                </SidebarMenu>
            </CollapsibleSidebarGroup>

            <CollapsibleSidebarGroup
                group-id="news"
                :label="t('navigation.groups.news')"
                :item-labels="labels.news.value"
                :visible="showNews"
                :search-query="search"
            >
                <SidebarMenu>
                    <SidebarLink
                        favorite-id="news-articles"
                        :label="t('navigation.articles')"
                        :icon="Newspaper"
                        :href="newsIndex().url"
                        :search-query="search"
                    />
                    <SidebarLink
                        favorite-id="news-comments"
                        :label="t('navigation.comments')"
                        :icon="MessageSquare"
                        :href="newsCommentsIndex().url"
                        :search-query="search"
                    />
                </SidebarMenu>
            </CollapsibleSidebarGroup>

            <CollapsibleSidebarGroup
                group-id="announcements"
                :label="t('navigation.groups.announcement')"
                :item-labels="labels.announcements.value"
                :visible="showAnnouncements"
                :search-query="search"
            >
                <SidebarMenu>
                    <SidebarLink
                        favorite-id="announcements"
                        :label="t('navigation.announcements')"
                        :icon="Megaphone"
                        :href="announcementsIndex().url"
                        :search-query="search"
                    />
                </SidebarMenu>
            </CollapsibleSidebarGroup>

            <CollapsibleSidebarGroup
                group-id="events"
                :label="t('navigation.groups.event')"
                :item-labels="labels.events.value"
                :visible="showEvents"
                :search-query="search"
            >
                <SidebarMenu>
                    <SidebarLink
                        v-if="can(Permission.ManageEvents)"
                        favorite-id="events"
                        :label="t('navigation.events')"
                        :icon="Calendar"
                        :href="eventsIndex().url"
                        :search-query="search"
                    />
                    <SidebarLink
                        v-if="can(Permission.ManageEvents)"
                        favorite-id="event-dashboard"
                        :label="t('navigation.eventDashboard')"
                        :icon="Gauge"
                        :href="eventsDashboard().url"
                        :search-query="search"
                    />
                    <SidebarLink
                        v-if="can(Permission.ManagePrograms)"
                        favorite-id="programs"
                        :label="t('navigation.programs')"
                        :icon="ClipboardList"
                        :href="programsIndex().url"
                        :search-query="search"
                    />
                    <SidebarLink
                        v-if="can(Permission.ManageVenues)"
                        favorite-id="venues"
                        :label="t('navigation.venues')"
                        :icon="MapPin"
                        :href="venuesIndex().url"
                        :search-query="search"
                    />
                    <SidebarLink
                        v-if="can(Permission.ManageSeatPlans)"
                        favorite-id="seat-plans"
                        :label="t('navigation.seatPlans')"
                        :icon="Grid2x2"
                        :href="seatPlansIndex().url"
                        :search-query="search"
                    />
                    <SidebarLink
                        v-if="can(Permission.ManageOrgaTeams)"
                        favorite-id="orga-teams"
                        label="Orga-Teams"
                        :icon="UsersRound"
                        :href="orgaTeamsIndex().url"
                        :search-query="search"
                    />
                </SidebarMenu>
            </CollapsibleSidebarGroup>

            <CollapsibleSidebarGroup
                group-id="games"
                :label="t('navigation.groups.games')"
                :item-labels="labels.games.value"
                :visible="showGames"
                :search-query="search"
            >
                <SidebarMenu>
                    <SidebarLink
                        favorite-id="games"
                        :label="t('navigation.games')"
                        :icon="Gamepad2"
                        :href="gamesIndex().url"
                        :search-query="search"
                    />
                </SidebarMenu>
            </CollapsibleSidebarGroup>

            <CollapsibleSidebarGroup
                group-id="sponsoring"
                :label="t('navigation.groups.sponsoring')"
                :item-labels="labels.sponsoring.value"
                :visible="showSponsoring"
                :search-query="search"
            >
                <SidebarMenu>
                    <SidebarLink
                        v-if="can(Permission.ManageSponsors)"
                        favorite-id="sponsors"
                        :label="t('navigation.sponsors')"
                        :icon="Handshake"
                        :href="sponsorsIndex().url"
                        :search-query="search"
                    />
                    <SidebarLink
                        v-if="
                            !can(Permission.ManageSponsors) &&
                            can(Permission.ManageAssignedSponsors)
                        "
                        favorite-id="my-sponsors"
                        :label="t('navigation.mySponsors')"
                        :icon="Handshake"
                        :href="sponsorsIndex().url"
                        :search-query="search"
                    />
                    <SidebarLink
                        v-if="can(Permission.ManageSponsorLevels)"
                        favorite-id="sponsor-levels"
                        :label="t('navigation.sponsorLevels')"
                        :icon="Palette"
                        :href="sponsorLevelsIndex().url"
                        :search-query="search"
                    />
                </SidebarMenu>
            </CollapsibleSidebarGroup>

            <CollapsibleSidebarGroup
                group-id="ticketing"
                :label="t('navigation.groups.ticketing')"
                :item-labels="labels.ticketing.value"
                :visible="showTicketing"
                :search-query="search"
            >
                <SidebarMenu>
                    <SidebarLink
                        favorite-id="ticket-types"
                        :label="t('navigation.ticketTypes')"
                        :icon="Rows3"
                        :href="ticketTypesIndex().url"
                        :search-query="search"
                    />
                    <SidebarLink
                        favorite-id="ticket-categories"
                        :label="t('navigation.ticketCategories')"
                        :icon="Tag"
                        :href="ticketCategoriesIndex().url"
                        :search-query="search"
                    />
                    <SidebarLink
                        favorite-id="ticket-addons"
                        :label="t('navigation.ticketAddons')"
                        :icon="Puzzle"
                        :href="ticketAddonsIndex().url"
                        :search-query="search"
                    />
                    <SidebarLink
                        favorite-id="vouchers"
                        :label="t('navigation.vouchers')"
                        :icon="Gift"
                        :href="vouchersIndex().url"
                        :search-query="search"
                    />
                </SidebarMenu>
            </CollapsibleSidebarGroup>

            <CollapsibleSidebarGroup
                group-id="competition"
                :label="t('navigation.groups.competition')"
                :item-labels="labels.competition.value"
                :visible="showCompetition"
                :search-query="search"
            >
                <SidebarMenu>
                    <SidebarLink
                        favorite-id="competitions"
                        :label="t('navigation.competitions')"
                        :icon="Swords"
                        :href="competitionsIndex().url"
                        :search-query="search"
                    />
                    <SidebarLink
                        favorite-id="admin-teams"
                        :label="t('navigation.teams')"
                        :icon="Users"
                        :href="adminTeamsIndex().url"
                        :search-query="search"
                    />
                    <SidebarLink
                        favorite-id="competition-board"
                        :label="t('navigation.competitionBoard')"
                        :icon="ChartGantt"
                        href="/backstage/competition-board"
                        :search-query="search"
                    />
                </SidebarMenu>
            </CollapsibleSidebarGroup>

            <CollapsibleSidebarGroup
                group-id="orchestration"
                :label="t('navigation.groups.orchestration')"
                :item-labels="labels.orchestration.value"
                :visible="showOrchestration"
                :search-query="search"
            >
                <SidebarMenu>
                    <SidebarLink
                        v-if="can(Permission.ManageGameServers)"
                        favorite-id="game-servers"
                        :label="t('navigation.gameServers')"
                        :icon="Server"
                        :href="gameServersIndex().url"
                        :search-query="search"
                    />
                    <SidebarLink
                        favorite-id="orchestration-jobs"
                        :label="t('navigation.orchestration')"
                        :icon="Radio"
                        :href="orchestrationJobsIndex().url"
                        :search-query="search"
                    />
                </SidebarMenu>
            </CollapsibleSidebarGroup>

            <CollapsibleSidebarGroup
                group-id="integrations"
                :label="t('navigation.groups.integrations')"
                :item-labels="labels.integrations.value"
                :visible="showIntegrations"
                :search-query="search"
            >
                <SidebarMenu>
                    <SidebarLink
                        v-if="can(Permission.ManageIntegrations)"
                        favorite-id="integrations"
                        :label="t('navigation.lanApps')"
                        :icon="Cog"
                        :href="integrationsIndex().url"
                        :search-query="search"
                    />
                    <SidebarLink
                        v-if="can(Permission.ManageWebhooks)"
                        favorite-id="webhooks"
                        :label="t('navigation.webhooks')"
                        :icon="Webhook"
                        :href="webhooksIndex().url"
                        :search-query="search"
                    />
                </SidebarMenu>
            </CollapsibleSidebarGroup>

            <CollapsibleSidebarGroup
                group-id="shop"
                :label="t('navigation.groups.shop')"
                :item-labels="labels.shop.value"
                :visible="showShop"
                :search-query="search"
            >
                <SidebarMenu>
                    <SidebarLink
                        favorite-id="shop-settings"
                        :label="t('navigation.settings')"
                        :icon="Cog"
                        :href="shopSettingsIndex().url"
                        :search-query="search"
                    />
                    <SidebarLink
                        favorite-id="orders"
                        :label="t('navigation.orders')"
                        :icon="ShoppingCart"
                        :href="ordersIndex().url"
                        :search-query="search"
                    />
                    <SidebarLink
                        favorite-id="admin-tickets"
                        :label="t('navigation.ticketsAdmin')"
                        :icon="TicketCheck"
                        :href="adminTicketsIndex().url"
                        :search-query="search"
                    />
                    <SidebarLink
                        favorite-id="purchase-requirements"
                        :label="t('navigation.purchaseRequirements')"
                        :icon="ShieldCheck"
                        :href="purchaseRequirementsIndex().url"
                        :search-query="search"
                    />
                    <SidebarLink
                        favorite-id="purchase-conditions"
                        :label="t('navigation.purchaseConditions')"
                        :icon="FileCheck"
                        :href="globalPurchaseConditionsIndex().url"
                        :search-query="search"
                    />
                    <SidebarLink
                        favorite-id="payment-conditions"
                        :label="t('navigation.paymentConditions')"
                        :icon="CreditCard"
                        :href="paymentProviderConditionsIndex().url"
                        :search-query="search"
                    />
                </SidebarMenu>
            </CollapsibleSidebarGroup>
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
