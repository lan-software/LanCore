<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ExternalLink, Users } from 'lucide-vue-next';
import UserCompetitionController from '@/actions/App/Domain/Competition/Http/Controllers/UserCompetitionController';
import TeamController from '@/actions/App/Domain/Competition/Http/Controllers/TeamController';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as myCompetitionsRoute } from '@/routes/my-competitions';
import type { BreadcrumbItem } from '@/types';
import type { Competition, CompetitionTeam } from '@/types/domain';

const props = defineProps<{
    competition: Competition;
    userTeam: CompetitionTeam | null;
    bracketUrl: string | null;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'My Competitions', href: myCompetitionsRoute().url },
    {
        title: props.competition.name,
        href: UserCompetitionController.show(props.competition.id).url,
    },
];

const createTeamForm = useForm({ name: '', tag: '' });

function submitCreateTeam() {
    createTeamForm.post(
        TeamController.store(props.competition.id).url,
        { preserveScroll: true },
    );
}
</script>

<template>
    <Head :title="competition.name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4">
            <div>
                <Link
                    :href="myCompetitionsRoute().url"
                    class="text-sm text-muted-foreground hover:text-foreground"
                >
                    &larr; Back to My Competitions
                </Link>
            </div>

            <!-- Header -->
            <div>
                <h1 class="text-2xl font-bold">{{ competition.name }}</h1>
                <div class="mt-1 flex items-center gap-2">
                    <Badge variant="outline" class="capitalize">
                        {{ competition.status.replace(/_/g, ' ') }}
                    </Badge>
                    <span
                        v-if="competition.game"
                        class="text-sm text-muted-foreground"
                    >
                        {{ competition.game.name }}
                    </span>
                    <span class="text-sm capitalize text-muted-foreground">
                        {{ competition.type }} &middot;
                        {{ competition.stage_type.replace(/_/g, ' ') }}
                    </span>
                </div>
                <p
                    v-if="competition.description"
                    class="mt-2 text-sm text-muted-foreground"
                >
                    {{ competition.description }}
                </p>
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Main content -->
                <div class="space-y-6 lg:col-span-2">
                    <!-- Bracket Link -->
                    <div
                        v-if="bracketUrl"
                        class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
                    >
                        <h3 class="mb-2 text-sm font-semibold">
                            Bracket View
                        </h3>
                        <a
                            :href="bracketUrl"
                            target="_blank"
                            rel="noopener"
                            class="inline-flex items-center gap-1 text-sm text-primary hover:underline"
                        >
                            <ExternalLink class="size-3" />
                            Open Bracket in LanBrackets
                        </a>
                    </div>

                    <!-- Your Team -->
                    <div
                        v-if="userTeam"
                        class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
                    >
                        <h3 class="mb-3 text-sm font-semibold">
                            Your Team: {{ userTeam.name }}
                            <span
                                v-if="userTeam.tag"
                                class="text-muted-foreground"
                                >[{{ userTeam.tag }}]</span
                            >
                        </h3>
                        <div class="space-y-1">
                            <div
                                v-for="member in userTeam.active_members"
                                :key="member.id"
                                class="flex items-center gap-2 text-sm"
                            >
                                <Users class="size-3 text-muted-foreground" />
                                <span>{{ member.user?.name }}</span>
                                <Badge
                                    v-if="
                                        member.user_id ===
                                        userTeam.captain_user_id
                                    "
                                    variant="outline"
                                    class="text-[10px]"
                                >
                                    Captain
                                </Badge>
                            </div>
                        </div>

                        <div
                            v-if="competition.status === 'registration_open'"
                            class="mt-3"
                        >
                            <Button
                                variant="outline"
                                size="sm"
                                @click="
                                    $inertia.post(
                                        TeamController.leave(
                                            competition.id,
                                            userTeam.id,
                                        ).url,
                                    )
                                "
                            >
                                Leave Team
                            </Button>
                        </div>
                    </div>

                    <!-- Create Team (if no team and registration open) -->
                    <div
                        v-else-if="
                            competition.status === 'registration_open'
                        "
                        class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
                    >
                        <h3 class="mb-3 text-sm font-semibold">
                            Create a Team
                        </h3>
                        <form
                            class="space-y-3"
                            @submit.prevent="submitCreateTeam"
                        >
                            <div class="grid gap-2">
                                <Label for="team-name">Team Name</Label>
                                <Input
                                    id="team-name"
                                    v-model="createTeamForm.name"
                                    required
                                    placeholder="e.g. Team Rocket"
                                />
                            </div>
                            <div class="grid gap-2">
                                <Label for="team-tag">Tag (optional)</Label>
                                <Input
                                    id="team-tag"
                                    v-model="createTeamForm.tag"
                                    maxlength="10"
                                    placeholder="e.g. TR"
                                />
                            </div>
                            <Button
                                type="submit"
                                size="sm"
                                :disabled="createTeamForm.processing"
                            >
                                Create Team
                            </Button>
                        </form>
                    </div>
                </div>

                <!-- Sidebar: All Teams -->
                <div>
                    <div
                        class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
                    >
                        <h3 class="mb-3 text-sm font-semibold">
                            All Teams ({{ competition.teams?.length ?? 0 }})
                        </h3>
                        <div class="space-y-2">
                            <div
                                v-for="team in competition.teams"
                                :key="team.id"
                                class="rounded-lg border px-3 py-2 text-sm"
                            >
                                <div class="font-medium">
                                    {{ team.name }}
                                    <span
                                        v-if="team.tag"
                                        class="text-muted-foreground"
                                    >
                                        [{{ team.tag }}]
                                    </span>
                                </div>
                                <div
                                    class="text-xs text-muted-foreground"
                                >
                                    {{
                                        team.active_members_count ??
                                        team.active_members?.length ??
                                        0
                                    }}
                                    members
                                </div>

                                <!-- Request to Join -->
                                <Button
                                    v-if="
                                        !userTeam &&
                                        competition.status ===
                                            'registration_open'
                                    "
                                    variant="outline"
                                    size="sm"
                                    class="mt-1"
                                    @click="
                                        $inertia.post(
                                            TeamController.requestJoin(
                                                competition.id,
                                                team.id,
                                            ).url,
                                        )
                                    "
                                >
                                    Request to Join
                                </Button>
                            </div>
                            <p
                                v-if="!competition.teams?.length"
                                class="text-xs text-muted-foreground"
                            >
                                No teams registered yet.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
