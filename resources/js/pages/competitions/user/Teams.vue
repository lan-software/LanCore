<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Shield, Swords, Users } from 'lucide-vue-next';
import { useI18n } from 'vue-i18n';
import EventSelector from '@/components/EventSelector.vue';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/AppLayout.vue';
import { show as myCompetitionShow } from '@/routes/my-competitions';
import { index as myTeamsRoute, show as myTeamShow } from '@/routes/my-teams';
import type { BreadcrumbItem } from '@/types';

const { t } = useI18n();

interface TeamItem {
    id: number;
    name: string;
    tag: string | null;
    is_captain: boolean;
    active_members_count: number;
    competition: {
        id: number;
        name: string;
        status: string;
        type: string;
        team_size: number | null;
        game: string | null;
    } | null;
}

defineProps<{
    teams: TeamItem[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: t('competitions.user.myTeams'), href: myTeamsRoute().url },
];

function statusColor(status: string): string {
    const map: Record<string, string> = {
        registration_open:
            'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400',
        registration_closed:
            'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-400',
        running:
            'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400',
        finished:
            'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-400',
        archived:
            'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-500',
    };

    return (
        map[status] ??
        'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'
    );
}
</script>

<template>
    <Head :title="$t('competitions.user.myTeams')" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4">
            <div>
                <h1 class="text-2xl font-bold">
                    {{ $t('competitions.user.myTeams') }}
                </h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    {{ $t('competitions.user.teamsListDescription') }}
                </p>
            </div>

            <EventSelector variant="my" :sidebar="false" />

            <div
                v-if="teams.length > 0"
                class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3"
            >
                <Link
                    v-for="team in teams"
                    :key="team.id"
                    :href="myTeamShow({ team: team.id }).url"
                    class="group flex flex-col rounded-xl border p-5 transition-colors hover:border-foreground/20"
                >
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <h3
                                class="font-semibold transition-colors group-hover:text-primary"
                            >
                                {{ team.name }}
                            </h3>
                            <span
                                v-if="team.tag"
                                class="text-xs text-muted-foreground"
                                >[{{ team.tag }}]</span
                            >
                        </div>
                        <Badge
                            v-if="team.is_captain"
                            variant="outline"
                            class="text-[10px]"
                        >
                            <Shield class="mr-0.5 size-2.5" />
                            {{ $t('competitions.captain') }}
                        </Badge>
                    </div>

                    <div v-if="team.competition" class="mt-3 space-y-1.5">
                        <Link
                            :href="
                                myCompetitionShow({
                                    competition: team.competition.id,
                                }).url
                            "
                            class="text-sm font-medium text-primary hover:underline"
                            @click.stop
                        >
                            {{ team.competition.name }}
                        </Link>
                        <div class="flex items-center gap-2">
                            <Badge
                                :class="statusColor(team.competition.status)"
                                class="text-[10px] capitalize"
                            >
                                {{ team.competition.status.replace(/_/g, ' ') }}
                            </Badge>
                            <span
                                v-if="team.competition.game"
                                class="text-xs text-muted-foreground"
                                >{{ team.competition.game }}</span
                            >
                        </div>
                    </div>

                    <div
                        class="mt-auto flex items-center gap-3 pt-3 text-xs text-muted-foreground"
                    >
                        <span class="flex items-center gap-1">
                            <Users class="size-3" />
                            {{ team.active_members_count
                            }}<template v-if="team.competition?.team_size">
                                / {{ team.competition.team_size }}</template
                            >
                        </span>
                    </div>
                </Link>
            </div>

            <div
                v-else
                class="rounded-xl border border-dashed p-12 text-center"
            >
                <Swords class="mx-auto size-10 text-muted-foreground/50" />
                <h3 class="mt-4 text-sm font-medium">
                    {{ $t('competitions.user.noTeamsYet') }}
                </h3>
                <p class="mt-1 text-sm text-muted-foreground">
                    {{ $t('competitions.user.teamsEmptyHint') }}
                </p>
            </div>
        </div>
    </AppLayout>
</template>
