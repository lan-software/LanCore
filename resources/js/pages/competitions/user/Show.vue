<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ExternalLink, Users } from 'lucide-vue-next';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import TeamController from '@/actions/App/Domain/Competition/Http/Controllers/TeamController';
import UserCompetitionController from '@/actions/App/Domain/Competition/Http/Controllers/UserCompetitionController';
import MailLetterAnimation from '@/components/MailLetterAnimation.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as myCompetitionsRoute } from '@/routes/my-competitions';
import type { BreadcrumbItem } from '@/types';
import type { Competition, CompetitionTeam } from '@/types/domain';

const { t } = useI18n();

const props = defineProps<{
    competition: Competition;
    userTeam: CompetitionTeam | null;
    bracketUrl: string | null;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: t('navigation.myCompetitions'), href: myCompetitionsRoute().url },
    {
        title: props.competition.name,
        href: UserCompetitionController.show(props.competition.id).url,
    },
];

const createTeamForm = useForm({ name: '', tag: '' });

function submitCreateTeam() {
    createTeamForm.post(TeamController.store(props.competition.id).url, {
        preserveScroll: true,
    });
}

const leaving = ref(false);

const page = usePage();

function leaveTeam() {
    if (!props.userTeam) {
        return;
    }

    const authUserId = (
        page.props.auth as { user?: { id: number } } | undefined
    )?.user?.id;
    const isCaptain =
        authUserId != null && props.userTeam.captain_user_id === authUserId;
    const msg = isCaptain
        ? t('competitions.user.leaveCaptainConfirm')
        : t('competitions.user.leaveTeamConfirm', { name: props.userTeam.name });

    if (!window.confirm(msg)) {
        return;
    }

    leaving.value = true;
    router.post(
        TeamController.leave({
            competition: props.competition.id,
            team: props.userTeam.id,
        }).url,
        {},
        { onFinish: () => (leaving.value = false) },
    );
}

const requestingTeamId = ref<number | null>(null);
const mailAnimTeamId = ref<number | null>(null);

function requestJoin(teamId: number) {
    requestingTeamId.value = teamId;
    router.post(
        TeamController.requestJoin({
            competition: props.competition.id,
            team: teamId,
        }).url,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                mailAnimTeamId.value = teamId;
            },
            onFinish: () => {
                requestingTeamId.value = null;
            },
        },
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
                    {{ $t('competitions.user.backToMyCompetitions') }}
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
                    <span class="text-sm text-muted-foreground capitalize">
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
                            {{ $t('competitions.user.bracketView') }}
                        </h3>
                        <a
                            :href="bracketUrl"
                            target="_blank"
                            rel="noopener"
                            class="inline-flex items-center gap-1 text-sm text-primary hover:underline"
                        >
                            <ExternalLink class="size-3" />
                            {{ $t('competitions.user.openBracket') }}
                        </a>
                    </div>

                    <!-- Your Team -->
                    <div
                        v-if="userTeam"
                        class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
                    >
                        <h3 class="mb-3 text-sm font-semibold">
                            {{
                                $t('competitions.user.yourTeam', {
                                    name: userTeam.name,
                                })
                            }}
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
                                    {{ $t('competitions.captain') }}
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
                                :disabled="leaving"
                                @click="leaveTeam"
                            >
                                {{
                                    leaving
                                        ? $t('competitions.user.leavingTeam')
                                        : $t('competitions.user.leaveTeam')
                                }}
                            </Button>
                        </div>
                    </div>

                    <!-- Create Team (if no team and registration open) -->
                    <div
                        v-else-if="competition.status === 'registration_open'"
                        class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
                    >
                        <h3 class="mb-3 text-sm font-semibold">
                            {{ $t('competitions.user.createTeamHeading') }}
                        </h3>
                        <form
                            class="space-y-3"
                            @submit.prevent="submitCreateTeam"
                        >
                            <div class="grid gap-2">
                                <Label for="team-name">{{
                                    $t('competitions.user.teamName')
                                }}</Label>
                                <Input
                                    id="team-name"
                                    v-model="createTeamForm.name"
                                    required
                                    :placeholder="
                                        $t('competitions.user.teamNamePlaceholder')
                                    "
                                />
                            </div>
                            <div class="grid gap-2">
                                <Label for="team-tag">{{
                                    $t('competitions.user.tagOptional')
                                }}</Label>
                                <Input
                                    id="team-tag"
                                    v-model="createTeamForm.tag"
                                    maxlength="10"
                                    :placeholder="
                                        $t('competitions.user.tagPlaceholder')
                                    "
                                />
                            </div>
                            <Button
                                type="submit"
                                size="sm"
                                :disabled="createTeamForm.processing"
                            >
                                {{ $t('competitions.user.submitCreateTeam') }}
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
                            {{
                                $t('competitions.user.allTeams', {
                                    count: competition.teams?.length ?? 0,
                                })
                            }}
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
                                <div class="text-xs text-muted-foreground">
                                    {{
                                        $t('competitions.user.membersCount', {
                                            count:
                                                team.active_members_count ??
                                                team.active_members?.length ??
                                                0,
                                        })
                                    }}
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
                                    :disabled="requestingTeamId === team.id"
                                    @click="requestJoin(team.id)"
                                >
                                    {{
                                        requestingTeamId === team.id
                                            ? $t('competitions.user.sendingRequest')
                                            : $t('competitions.user.requestToJoin')
                                    }}
                                </Button>
                                <MailLetterAnimation
                                    :show="mailAnimTeamId === team.id"
                                    @done="mailAnimTeamId = null"
                                />
                            </div>
                            <p
                                v-if="!competition.teams?.length"
                                class="text-xs text-muted-foreground"
                            >
                                {{ $t('competitions.user.noTeams') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
