<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    Calendar,
    CalendarCheck,
    CalendarClock,
    CircleDollarSign,
    ClipboardList,
    Clock,
    CreditCard,
    Gamepad2,
    Handshake,
    MapPin,
    Palette,
    Receipt,
    Shield,
    ShieldCheck,
    Ticket,
    TicketCheck,
    Users,
} from 'lucide-vue-next';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

interface DashboardStats {
    counts: {
        users: number;
        events: number;
        programs: number;
        time_slots: number;
        venues: number;
        sponsors: number;
        sponsor_levels: number;
        tickets: number;
        ticket_types: number;
        addons: number;
        orders: number;
        games: number;
        game_modes: number;
        seat_plans: number;
        vouchers: number;
    };
    events: {
        upcoming: number;
        past: number;
        published: number;
        draft: number;
    };
    tickets: {
        active: number;
        checked_in: number;
        cancelled: number;
    };
    orders: {
        pending: number;
        completed: number;
        failed: number;
        refunded: number;
        total_revenue: number;
    };
    roles: Record<string, number>;
    lastActiveUsers: {
        id: number;
        name: string;
        email: string;
        last_activity: string;
    }[];
}

defineProps<{
    stats: DashboardStats;
    isAdmin: boolean;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
    },
];

function formatRelativeTime(isoString: string): string {
    const date = new Date(isoString);
    const now = new Date();
    const diffMs = now.getTime() - date.getTime();
    const diffMinutes = Math.floor(diffMs / 60000);

    if (diffMinutes < 1) {
        return 'Just now';
    }

    if (diffMinutes < 60) {
        return `${diffMinutes}m ago`;
    }

    const diffHours = Math.floor(diffMinutes / 60);

    if (diffHours < 24) {
        return `${diffHours}h ago`;
    }

    const diffDays = Math.floor(diffHours / 24);

    return `${diffDays}d ago`;
}

const roleLabels: Record<string, string> = {
    superadmin: 'Superadmins',
    admin: 'Admins',
    sponsor_manager: 'Sponsor Managers',
    user: 'Users',
};

function formatCents(cents: number): string {
    return new Intl.NumberFormat('de-DE', {
        style: 'currency',
        currency: 'EUR',
    }).format(cents / 100);
}
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4">
            <template v-if="isAdmin">
                <!-- Entity Counts -->
                <div>
                    <h2 class="mb-3 text-lg font-semibold">Overview</h2>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <Card>
                            <CardHeader
                                class="flex flex-row items-center justify-between pb-2"
                            >
                                <CardDescription>Users</CardDescription>
                                <Users class="size-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div class="text-2xl font-bold">
                                    {{ stats.counts.users }}
                                </div>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardHeader
                                class="flex flex-row items-center justify-between pb-2"
                            >
                                <CardDescription>Events</CardDescription>
                                <Calendar
                                    class="size-4 text-muted-foreground"
                                />
                            </CardHeader>
                            <CardContent>
                                <div class="text-2xl font-bold">
                                    {{ stats.counts.events }}
                                </div>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardHeader
                                class="flex flex-row items-center justify-between pb-2"
                            >
                                <CardDescription>Programs</CardDescription>
                                <ClipboardList
                                    class="size-4 text-muted-foreground"
                                />
                            </CardHeader>
                            <CardContent>
                                <div class="text-2xl font-bold">
                                    {{ stats.counts.programs }}
                                </div>
                                <p class="text-xs text-muted-foreground">
                                    {{ stats.counts.time_slots }} time slots
                                </p>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardHeader
                                class="flex flex-row items-center justify-between pb-2"
                            >
                                <CardDescription>Venues</CardDescription>
                                <MapPin class="size-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div class="text-2xl font-bold">
                                    {{ stats.counts.venues }}
                                </div>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardHeader
                                class="flex flex-row items-center justify-between pb-2"
                            >
                                <CardDescription>Sponsors</CardDescription>
                                <Handshake
                                    class="size-4 text-muted-foreground"
                                />
                            </CardHeader>
                            <CardContent>
                                <div class="text-2xl font-bold">
                                    {{ stats.counts.sponsors }}
                                </div>
                                <p class="text-xs text-muted-foreground">
                                    {{ stats.counts.sponsor_levels }} levels
                                </p>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardHeader
                                class="flex flex-row items-center justify-between pb-2"
                            >
                                <CardDescription>Tickets</CardDescription>
                                <Ticket class="size-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div class="text-2xl font-bold">
                                    {{ stats.counts.tickets }}
                                </div>
                                <p class="text-xs text-muted-foreground">
                                    {{ stats.counts.ticket_types }} types ·
                                    {{ stats.counts.addons }} addons
                                </p>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardHeader
                                class="flex flex-row items-center justify-between pb-2"
                            >
                                <CardDescription>Orders</CardDescription>
                                <Receipt class="size-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div class="text-2xl font-bold">
                                    {{ stats.counts.orders }}
                                </div>
                                <p class="text-xs text-muted-foreground">
                                    {{ stats.counts.vouchers }} vouchers
                                </p>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardHeader
                                class="flex flex-row items-center justify-between pb-2"
                            >
                                <CardDescription>Games</CardDescription>
                                <Gamepad2
                                    class="size-4 text-muted-foreground"
                                />
                            </CardHeader>
                            <CardContent>
                                <div class="text-2xl font-bold">
                                    {{ stats.counts.games }}
                                </div>
                                <p class="text-xs text-muted-foreground">
                                    {{ stats.counts.game_modes }} game modes ·
                                    {{ stats.counts.seat_plans }} seat plans
                                </p>
                            </CardContent>
                        </Card>
                    </div>
                </div>

                <!-- Events Breakdown -->
                <div>
                    <h2 class="mb-3 text-lg font-semibold">Events</h2>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <Card>
                            <CardHeader
                                class="flex flex-row items-center justify-between pb-2"
                            >
                                <CardDescription>Upcoming</CardDescription>
                                <CalendarClock
                                    class="size-4 text-muted-foreground"
                                />
                            </CardHeader>
                            <CardContent>
                                <div class="text-2xl font-bold">
                                    {{ stats.events.upcoming }}
                                </div>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardHeader
                                class="flex flex-row items-center justify-between pb-2"
                            >
                                <CardDescription>Past</CardDescription>
                                <CalendarCheck
                                    class="size-4 text-muted-foreground"
                                />
                            </CardHeader>
                            <CardContent>
                                <div class="text-2xl font-bold">
                                    {{ stats.events.past }}
                                </div>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardHeader
                                class="flex flex-row items-center justify-between pb-2"
                            >
                                <CardDescription>Published</CardDescription>
                                <Calendar
                                    class="size-4 text-muted-foreground"
                                />
                            </CardHeader>
                            <CardContent>
                                <div
                                    class="text-2xl font-bold text-green-600 dark:text-green-400"
                                >
                                    {{ stats.events.published }}
                                </div>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardHeader
                                class="flex flex-row items-center justify-between pb-2"
                            >
                                <CardDescription>Draft</CardDescription>
                                <Calendar
                                    class="size-4 text-muted-foreground"
                                />
                            </CardHeader>
                            <CardContent>
                                <div
                                    class="text-2xl font-bold text-yellow-600 dark:text-yellow-400"
                                >
                                    {{ stats.events.draft }}
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>

                <!-- Ticketing & Orders -->
                <div class="grid gap-6 lg:grid-cols-2">
                    <!-- Tickets Breakdown -->
                    <div>
                        <h2 class="mb-3 text-lg font-semibold">Tickets</h2>
                        <Card>
                            <CardContent class="pt-6">
                                <div class="space-y-4">
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <div class="flex items-center gap-2">
                                            <Ticket
                                                class="size-4 text-green-500"
                                            />
                                            <span class="text-sm font-medium"
                                                >Active</span
                                            >
                                        </div>
                                        <span class="text-sm font-bold">{{
                                            stats.tickets.active
                                        }}</span>
                                    </div>
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <div class="flex items-center gap-2">
                                            <TicketCheck
                                                class="size-4 text-blue-500"
                                            />
                                            <span class="text-sm font-medium"
                                                >Checked In</span
                                            >
                                        </div>
                                        <span class="text-sm font-bold">{{
                                            stats.tickets.checked_in
                                        }}</span>
                                    </div>
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <div class="flex items-center gap-2">
                                            <Ticket
                                                class="size-4 text-red-500"
                                            />
                                            <span class="text-sm font-medium"
                                                >Cancelled</span
                                            >
                                        </div>
                                        <span class="text-sm font-bold">{{
                                            stats.tickets.cancelled
                                        }}</span>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Orders Breakdown -->
                    <div>
                        <h2 class="mb-3 text-lg font-semibold">Orders</h2>
                        <Card>
                            <CardContent class="pt-6">
                                <div class="space-y-4">
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <div class="flex items-center gap-2">
                                            <CreditCard
                                                class="size-4 text-yellow-500"
                                            />
                                            <span class="text-sm font-medium"
                                                >Pending</span
                                            >
                                        </div>
                                        <span class="text-sm font-bold">{{
                                            stats.orders.pending
                                        }}</span>
                                    </div>
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <div class="flex items-center gap-2">
                                            <CreditCard
                                                class="size-4 text-green-500"
                                            />
                                            <span class="text-sm font-medium"
                                                >Completed</span
                                            >
                                        </div>
                                        <span class="text-sm font-bold">{{
                                            stats.orders.completed
                                        }}</span>
                                    </div>
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <div class="flex items-center gap-2">
                                            <CreditCard
                                                class="size-4 text-red-500"
                                            />
                                            <span class="text-sm font-medium"
                                                >Failed</span
                                            >
                                        </div>
                                        <span class="text-sm font-bold">{{
                                            stats.orders.failed
                                        }}</span>
                                    </div>
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <div class="flex items-center gap-2">
                                            <CreditCard
                                                class="size-4 text-muted-foreground"
                                            />
                                            <span class="text-sm font-medium"
                                                >Refunded</span
                                            >
                                        </div>
                                        <span class="text-sm font-bold">{{
                                            stats.orders.refunded
                                        }}</span>
                                    </div>
                                    <div class="border-t pt-4">
                                        <div
                                            class="flex items-center justify-between"
                                        >
                                            <div
                                                class="flex items-center gap-2"
                                            >
                                                <CircleDollarSign
                                                    class="size-4 text-green-600 dark:text-green-400"
                                                />
                                                <span
                                                    class="text-sm font-medium"
                                                    >Total Revenue</span
                                                >
                                            </div>
                                            <span
                                                class="text-sm font-bold text-green-600 dark:text-green-400"
                                                >{{
                                                    formatCents(
                                                        stats.orders
                                                            .total_revenue,
                                                    )
                                                }}</span
                                            >
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>

                <div class="grid gap-6 lg:grid-cols-2">
                    <!-- Role Distribution -->
                    <div>
                        <h2 class="mb-3 text-lg font-semibold">
                            Role Distribution
                        </h2>
                        <Card>
                            <CardContent class="pt-6">
                                <div class="space-y-4">
                                    <div
                                        v-for="(count, role) in stats.roles"
                                        :key="role"
                                        class="flex items-center justify-between"
                                    >
                                        <div class="flex items-center gap-2">
                                            <ShieldCheck
                                                v-if="role === 'superadmin'"
                                                class="size-4 text-purple-500"
                                            />
                                            <Shield
                                                v-else-if="role === 'admin'"
                                                class="size-4 text-blue-500"
                                            />
                                            <Palette
                                                v-else-if="
                                                    role === 'sponsor_manager'
                                                "
                                                class="size-4 text-orange-500"
                                            />
                                            <Users
                                                v-else
                                                class="size-4 text-muted-foreground"
                                            />
                                            <span class="text-sm font-medium">{{
                                                roleLabels[role as string] ??
                                                role
                                            }}</span>
                                        </div>
                                        <span class="text-sm font-bold">{{
                                            count
                                        }}</span>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Last Active Users -->
                    <div>
                        <h2 class="mb-3 text-lg font-semibold">
                            Recently Active Users
                        </h2>
                        <Card>
                            <CardContent class="pt-6">
                                <div
                                    v-if="stats.lastActiveUsers.length === 0"
                                    class="text-sm text-muted-foreground"
                                >
                                    No recent activity.
                                </div>
                                <div v-else class="space-y-4">
                                    <div
                                        v-for="user in stats.lastActiveUsers"
                                        :key="user.id"
                                        class="flex items-center justify-between"
                                    >
                                        <div class="min-w-0 flex-1">
                                            <p
                                                class="truncate text-sm font-medium"
                                            >
                                                {{ user.name }}
                                            </p>
                                            <p
                                                class="truncate text-xs text-muted-foreground"
                                            >
                                                {{ user.email }}
                                            </p>
                                        </div>
                                        <div
                                            class="ml-4 flex items-center gap-1 text-xs text-muted-foreground"
                                        >
                                            <Clock class="size-3" />
                                            <span>{{
                                                formatRelativeTime(
                                                    user.last_activity,
                                                )
                                            }}</span>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </template>

            <!-- Non-admin fallback -->
            <template v-else>
                <div class="flex flex-1 items-center justify-center">
                    <Card class="w-full max-w-md">
                        <CardHeader>
                            <CardTitle>Welcome</CardTitle>
                            <CardDescription
                                >You are logged in. Use the sidebar to
                                navigate.</CardDescription
                            >
                        </CardHeader>
                    </Card>
                </div>
            </template>
        </div>
    </AppLayout>
</template>
