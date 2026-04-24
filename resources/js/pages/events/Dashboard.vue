<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { CalendarRange, RefreshCw } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import Heading from '@/components/Heading.vue';
import StatCard from '@/components/StatCard.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableEmpty,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as eventsRoute } from '@/routes/events';
import type { BreadcrumbItem } from '@/types';

interface Stats {
    event: {
        id: number;
        name: string;
        start_date: string | null;
        end_date: string | null;
        status: string;
        seat_capacity: number | null;
    };
    headline: {
        ticketsSold: number;
        ticketsInSale: number;
        seatedUserCount: number;
        checkedIn: number;
        notCheckedIn: number;
        activeAssignees: number;
    };
    ticketTypes: {
        id: number;
        name: string;
        quota: number;
        sold: number;
        remaining: number;
        purchaseFrom: string | null;
        purchaseUntil: string | null;
        isOpenNow: boolean;
    }[];
    seating: {
        seatedCheckedIn: number;
        unseatedCheckedIn: number;
    };
    recentCheckins: {
        id: number;
        userName: string | null;
        ticketTypeName: string | null;
        action: string;
        decision: string | null;
        at: string | null;
    }[];
}

const props = defineProps<{
    stats: Stats | null;
    generatedAt: string;
}>();

const { t } = useI18n();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: eventsRoute().url },
    { title: t('navigation.eventDashboard'), href: '/events/dashboard' },
];

const isRefreshing = ref(false);

function refresh() {
    isRefreshing.value = true;
    router.reload({
        only: ['stats', 'generatedAt'],
        onFinish: () => {
            isRefreshing.value = false;
        },
    });
}

function formatRange(start: string | null, end: string | null): string {
    if (!start || !end) {
        return '';
    }

    const s = new Date(start);
    const e = new Date(end);
    const opts: Intl.DateTimeFormatOptions = {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    };

    return `${s.toLocaleString(undefined, opts)} – ${e.toLocaleString(undefined, opts)}`;
}

function formatDateTime(iso: string | null): string {
    if (!iso) {
        return '';
    }

    return new Date(iso).toLocaleString(undefined, {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

const generatedAtFormatted = computed(() =>
    new Date(props.generatedAt).toLocaleTimeString(undefined, {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    }),
);

const checkInProgress = computed(() => {
    if (!props.stats || props.stats.headline.activeAssignees === 0) {
        return 0;
    }

    return Math.round(
        (props.stats.headline.checkedIn / props.stats.headline.activeAssignees) *
            100,
    );
});
</script>

<template>
    <Head :title="t('navigation.eventDashboard')" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4">
            <div class="flex items-start justify-between gap-4">
                <Heading
                    :title="t('navigation.eventDashboard')"
                    :description="
                        stats
                            ? `${stats.event.name} · ${formatRange(stats.event.start_date, stats.event.end_date)}`
                            : t('eventDashboard.noEventDescription')
                    "
                />
                <div
                    v-if="stats"
                    class="flex items-center gap-3"
                >
                    <span class="text-xs text-muted-foreground">
                        {{ t('eventDashboard.lastRefreshed') }}
                        {{ generatedAtFormatted }}
                    </span>
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="isRefreshing"
                        @click="refresh"
                    >
                        <RefreshCw
                            class="size-4"
                            :class="{ 'animate-spin': isRefreshing }"
                        />
                        {{ t('eventDashboard.refresh') }}
                    </Button>
                </div>
            </div>

            <Card v-if="!stats">
                <CardContent
                    class="flex flex-col items-center gap-3 py-12 text-center"
                >
                    <CalendarRange
                        class="size-10 text-muted-foreground"
                    />
                    <p class="text-sm text-muted-foreground">
                        {{ t('eventDashboard.emptyHelp') }}
                    </p>
                </CardContent>
            </Card>

            <template v-else>
                <div
                    class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4"
                >
                    <StatCard
                        :title="t('eventDashboard.cards.ticketsSold')"
                        :value="stats.headline.ticketsSold"
                    />
                    <StatCard
                        :title="t('eventDashboard.cards.ticketsInSale')"
                        :value="stats.headline.ticketsInSale"
                        :subtext="t('eventDashboard.cards.ticketsInSaleHelp')"
                    />
                    <StatCard
                        :title="t('eventDashboard.cards.seatedUsers')"
                        :value="stats.headline.seatedUserCount"
                        :subtext="
                            stats.event.seat_capacity !== null
                                ? t('eventDashboard.cards.ofCapacity', {
                                      total: stats.event.seat_capacity,
                                  })
                                : undefined
                        "
                    />
                    <StatCard
                        :title="t('eventDashboard.cards.checkedIn')"
                        :value="stats.headline.checkedIn"
                        :subtext="
                            t('eventDashboard.cards.ofActive', {
                                total: stats.headline.activeAssignees,
                            })
                        "
                        :progress="checkInProgress"
                    />
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <Card>
                        <CardContent class="flex flex-col gap-2">
                            <div
                                class="text-sm font-medium text-muted-foreground"
                            >
                                {{
                                    t('eventDashboard.seating.seatedCheckedIn')
                                }}
                            </div>
                            <div
                                class="text-2xl font-semibold tabular-nums"
                            >
                                {{ stats.seating.seatedCheckedIn }}
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent class="flex flex-col gap-2">
                            <div
                                class="text-sm font-medium text-muted-foreground"
                            >
                                {{
                                    t(
                                        'eventDashboard.seating.unseatedCheckedIn',
                                    )
                                }}
                            </div>
                            <div
                                class="text-2xl font-semibold tabular-nums"
                            >
                                {{ stats.seating.unseatedCheckedIn }}
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <section class="flex flex-col gap-2">
                    <h2 class="text-lg font-semibold">
                        {{ t('eventDashboard.ticketTypes.heading') }}
                    </h2>
                    <div
                        class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
                    >
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>
                                        {{
                                            t('eventDashboard.ticketTypes.name')
                                        }}
                                    </TableHead>
                                    <TableHead class="text-right">
                                        {{
                                            t('eventDashboard.ticketTypes.sold')
                                        }}
                                    </TableHead>
                                    <TableHead class="text-right">
                                        {{
                                            t(
                                                'eventDashboard.ticketTypes.quota',
                                            )
                                        }}
                                    </TableHead>
                                    <TableHead class="text-right">
                                        {{
                                            t(
                                                'eventDashboard.ticketTypes.remaining',
                                            )
                                        }}
                                    </TableHead>
                                    <TableHead>
                                        {{
                                            t(
                                                'eventDashboard.ticketTypes.status',
                                            )
                                        }}
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <template v-if="stats.ticketTypes.length">
                                    <TableRow
                                        v-for="type in stats.ticketTypes"
                                        :key="type.id"
                                    >
                                        <TableCell class="font-medium">
                                            {{ type.name }}
                                        </TableCell>
                                        <TableCell
                                            class="text-right tabular-nums"
                                        >
                                            {{ type.sold }}
                                        </TableCell>
                                        <TableCell
                                            class="text-right tabular-nums"
                                        >
                                            {{ type.quota }}
                                        </TableCell>
                                        <TableCell
                                            class="text-right tabular-nums"
                                        >
                                            {{ type.remaining }}
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                :variant="
                                                    type.isOpenNow
                                                        ? 'default'
                                                        : 'secondary'
                                                "
                                            >
                                                {{
                                                    type.isOpenNow
                                                        ? t(
                                                              'eventDashboard.ticketTypes.onSale',
                                                          )
                                                        : t(
                                                              'eventDashboard.ticketTypes.closed',
                                                          )
                                                }}
                                            </Badge>
                                        </TableCell>
                                    </TableRow>
                                </template>
                                <TableEmpty v-else :colspan="5">
                                    {{ t('eventDashboard.ticketTypes.empty') }}
                                </TableEmpty>
                            </TableBody>
                        </Table>
                    </div>
                </section>

                <section class="flex flex-col gap-2">
                    <h2 class="text-lg font-semibold">
                        {{ t('eventDashboard.recentCheckins.heading') }}
                    </h2>
                    <div
                        class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
                    >
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>
                                        {{
                                            t(
                                                'eventDashboard.recentCheckins.user',
                                            )
                                        }}
                                    </TableHead>
                                    <TableHead>
                                        {{
                                            t(
                                                'eventDashboard.recentCheckins.ticketType',
                                            )
                                        }}
                                    </TableHead>
                                    <TableHead>
                                        {{
                                            t(
                                                'eventDashboard.recentCheckins.action',
                                            )
                                        }}
                                    </TableHead>
                                    <TableHead>
                                        {{
                                            t(
                                                'eventDashboard.recentCheckins.time',
                                            )
                                        }}
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <template v-if="stats.recentCheckins.length">
                                    <TableRow
                                        v-for="entry in stats.recentCheckins"
                                        :key="entry.id"
                                    >
                                        <TableCell>
                                            {{ entry.userName ?? '—' }}
                                        </TableCell>
                                        <TableCell>
                                            {{ entry.ticketTypeName ?? '—' }}
                                        </TableCell>
                                        <TableCell>{{ entry.action }}</TableCell>
                                        <TableCell>
                                            {{ formatDateTime(entry.at) }}
                                        </TableCell>
                                    </TableRow>
                                </template>
                                <TableEmpty v-else :colspan="4">
                                    {{ t('eventDashboard.recentCheckins.empty') }}
                                </TableEmpty>
                            </TableBody>
                        </Table>
                    </div>
                </section>
            </template>
        </div>
    </AppLayout>
</template>
