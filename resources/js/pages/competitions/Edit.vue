<script setup lang="ts">
import { Form, Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    AlertTriangle,
    CheckCircle2,
    ChevronRight,
    ExternalLink,
    Loader2,
    Play,
    Trophy,
    Users,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import CompetitionController from '@/actions/App/Domain/Competition/Http/Controllers/CompetitionController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as competitionsRoute } from '@/routes/competitions';
import type { BreadcrumbItem } from '@/types';
import type { Competition, Game } from '@/types/domain';

const { t } = useI18n();

const props = defineProps<{
    competition: Competition;
    games: Game[];
    events: { id: number; name: string; start_date: string }[];
    lanbracketsEnabled: boolean;
    lanbracketsBaseUrl: string;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: t('common.administration'), href: competitionsRoute().url },
    { title: t('navigation.competitions'), href: competitionsRoute().url },
    {
        title: props.competition.name,
        href: CompetitionController.edit(props.competition.id).url,
    },
];

// Status lifecycle
const allStatuses = computed(() => [
    { key: 'draft', label: t('competitions.lifecycle.draft') },
    {
        key: 'registration_open',
        label: t('competitions.lifecycle.registrationOpen'),
    },
    {
        key: 'registration_closed',
        label: t('competitions.lifecycle.registrationClosed'),
    },
    { key: 'running', label: t('competitions.lifecycle.running') },
    { key: 'finished', label: t('competitions.lifecycle.finished') },
    { key: 'archived', label: t('competitions.lifecycle.archived') },
]);

const currentStatusIndex = computed(() =>
    allStatuses.value.findIndex((s) => s.key === props.competition.status),
);

const statusTransitions = computed<
    Record<
        string,
        { label: string; target: string; variant: string; confirm?: string }
    >
>(() => ({
    draft: {
        label: t('competitions.transition.openRegistration'),
        target: 'registration_open',
        variant: 'default',
    },
    registration_open: {
        label: t('competitions.transition.closeRegistration'),
        target: 'registration_closed',
        variant: 'default',
        confirm: t('competitions.transition.closeRegistrationConfirm'),
    },
    registration_closed: {
        label: t('competitions.transition.goLive'),
        target: 'running',
        variant: 'default',
        confirm: t('competitions.transition.goLiveConfirm'),
    },
    running: {
        label: t('competitions.transition.markFinished'),
        target: 'finished',
        variant: 'destructive',
        confirm: t('competitions.transition.markFinishedConfirm'),
    },
    finished: {
        label: t('competitions.transition.archive'),
        target: 'archived',
        variant: 'outline',
    },
}));

const transition = computed(
    () => statusTransitions.value[props.competition.status] ?? null,
);
const transitionForm = useForm({ status: transition.value?.target ?? '' });
const transitioning = ref(false);

function submitTransition() {
    if (
        transition.value?.confirm &&
        !window.confirm(transition.value.confirm)
    ) {
        return;
    }

    transitioning.value = true;
    transitionForm.patch(
        CompetitionController.update(props.competition.id).url,
        {
            onFinish: () => (transitioning.value = false),
        },
    );
}

// Teams
const teamCount = computed(() => props.competition.teams?.length ?? 0);
const maxTeams = computed(() => props.competition.max_teams ?? 0);
const teamProgress = computed(() =>
    maxTeams.value > 0
        ? Math.min(100, (teamCount.value / maxTeams.value) * 100)
        : 0,
);

// LanBrackets
const bracketViewUrl = computed(() =>
    props.competition.lanbrackets_id &&
    props.competition.lanbrackets_share_token
        ? `${props.lanbracketsBaseUrl}/overlay/competitions/${props.competition.lanbrackets_id}?token=${props.competition.lanbrackets_share_token}`
        : null,
);

const lanbracketsAdminUrl = computed(() =>
    props.competition.lanbrackets_id
        ? `${props.lanbracketsBaseUrl}/competitions/${props.competition.lanbrackets_id}`
        : null,
);

// Can edit fields
const canEditDetails = computed(() =>
    ['draft', 'registration_open'].includes(props.competition.status),
);

// Delete
const deleting = ref(false);
function deleteCompetition() {
    if (
        !window.confirm(
            t('competitions.deleteConfirm', { name: props.competition.name }),
        )
    ) {
        return;
    }

    deleting.value = true;
    router.delete(CompetitionController.destroy(props.competition.id).url);
}

// Status badge color
function statusColor(status: string): string {
    const map: Record<string, string> = {
        draft: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
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

    return map[status] ?? '';
}
</script>

<template>
    <Head :title="$t('competitions.editTitle', { name: competition.name })" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4">
            <div class="flex items-center justify-between">
                <Link
                    :href="competitionsRoute().url"
                    class="text-sm text-muted-foreground hover:text-foreground"
                >
                    {{ $t('competitions.backToList') }}
                </Link>
                <Button
                    v-if="competition.status === 'draft'"
                    variant="ghost"
                    size="sm"
                    class="text-destructive hover:text-destructive"
                    :disabled="deleting"
                    @click="deleteCompetition"
                >
                    {{
                        deleting
                            ? $t('competitions.deleting')
                            : $t('common.delete')
                    }}
                </Button>
            </div>

            <!-- Status progress bar -->
            <div
                class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
            >
                <div class="flex items-center justify-between">
                    <template v-for="(s, i) in allStatuses" :key="s.key">
                        <div class="flex flex-col items-center gap-1.5">
                            <div
                                class="flex size-8 items-center justify-center rounded-full text-xs font-bold transition-colors"
                                :class="
                                    i <= currentStatusIndex
                                        ? 'bg-primary text-primary-foreground'
                                        : 'bg-muted text-muted-foreground'
                                "
                            >
                                <CheckCircle2
                                    v-if="i < currentStatusIndex"
                                    class="size-4"
                                />
                                <Play
                                    v-else-if="
                                        i === currentStatusIndex &&
                                        s.key === 'running'
                                    "
                                    class="size-3.5"
                                />
                                <Trophy
                                    v-else-if="
                                        i === currentStatusIndex &&
                                        s.key === 'finished'
                                    "
                                    class="size-3.5"
                                />
                                <span v-else>{{ i + 1 }}</span>
                            </div>
                            <span
                                class="text-[10px] font-medium"
                                :class="
                                    i <= currentStatusIndex
                                        ? 'text-foreground'
                                        : 'text-muted-foreground'
                                "
                            >
                                {{ s.label }}
                            </span>
                        </div>
                        <ChevronRight
                            v-if="i < allStatuses.length - 1"
                            class="mb-5 size-4 shrink-0 text-muted-foreground/40"
                        />
                    </template>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Main form -->
                <div class="space-y-6 lg:col-span-2">
                    <Form
                        v-bind="
                            CompetitionController.update.form(competition.id)
                        "
                        class="space-y-6"
                        v-slot="{ errors, processing }"
                    >
                        <Heading
                            variant="small"
                            :title="competition.name"
                            :description="
                                canEditDetails
                                    ? $t(
                                          'competitions.form.editDescriptionEditable',
                                      )
                                    : $t(
                                          'competitions.form.editDescriptionLocked',
                                      )
                            "
                        />

                        <div class="grid gap-2">
                            <Label for="name">{{ $t('common.name') }}</Label>
                            <Input
                                id="name"
                                name="name"
                                :default-value="competition.name"
                                :disabled="!canEditDetails"
                            />
                            <InputError :message="errors.name" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="description">{{
                                $t('common.description')
                            }}</Label>
                            <Textarea
                                id="description"
                                name="description"
                                :default-value="competition.description ?? ''"
                                rows="3"
                                :disabled="!canEditDetails"
                            />
                            <InputError :message="errors.description" />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="grid gap-2">
                                <Label for="game_id">{{
                                    $t('competitions.form.game')
                                }}</Label>
                                <select
                                    id="game_id"
                                    name="game_id"
                                    :disabled="!canEditDetails"
                                    class="rounded-md border border-input bg-background px-3 py-2 text-sm disabled:opacity-50"
                                >
                                    <option value="">
                                        {{ $t('common.none') }}
                                    </option>
                                    <option
                                        v-for="game in games"
                                        :key="game.id"
                                        :value="game.id"
                                        :selected="
                                            game.id === competition.game_id
                                        "
                                    >
                                        {{ game.name }}
                                    </option>
                                </select>
                                <InputError :message="errors.game_id" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="event_id">{{
                                    $t('competitions.form.event')
                                }}</Label>
                                <select
                                    id="event_id"
                                    name="event_id"
                                    :disabled="!canEditDetails"
                                    class="rounded-md border border-input bg-background px-3 py-2 text-sm disabled:opacity-50"
                                >
                                    <option value="">
                                        {{ $t('common.none') }}
                                    </option>
                                    <option
                                        v-for="event in events"
                                        :key="event.id"
                                        :value="event.id"
                                        :selected="
                                            event.id === competition.event_id
                                        "
                                    >
                                        {{ event.name }}
                                    </option>
                                </select>
                                <InputError :message="errors.event_id" />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="grid gap-2">
                                <Label for="team_size">{{
                                    $t('competitions.form.teamSize')
                                }}</Label>
                                <Input
                                    id="team_size"
                                    name="team_size"
                                    type="number"
                                    min="1"
                                    :default-value="
                                        String(competition.team_size ?? '')
                                    "
                                    :disabled="!canEditDetails"
                                />
                                <InputError :message="errors.team_size" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="max_teams">{{
                                    $t('competitions.form.maxTeams')
                                }}</Label>
                                <Input
                                    id="max_teams"
                                    name="max_teams"
                                    type="number"
                                    min="2"
                                    :default-value="
                                        String(competition.max_teams ?? '')
                                    "
                                    :disabled="!canEditDetails"
                                />
                                <InputError :message="errors.max_teams" />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="grid gap-2">
                                <Label for="starts_at">{{
                                    $t('competitions.form.startsAt')
                                }}</Label>
                                <Input
                                    id="starts_at"
                                    name="starts_at"
                                    type="datetime-local"
                                    :default-value="
                                        competition.starts_at?.slice(0, 16) ??
                                        ''
                                    "
                                    :disabled="!canEditDetails"
                                />
                                <InputError :message="errors.starts_at" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="ends_at">{{
                                    $t('competitions.form.endsAt')
                                }}</Label>
                                <Input
                                    id="ends_at"
                                    name="ends_at"
                                    type="datetime-local"
                                    :default-value="
                                        competition.ends_at?.slice(0, 16) ?? ''
                                    "
                                    :disabled="!canEditDetails"
                                />
                                <InputError :message="errors.ends_at" />
                            </div>
                        </div>

                        <div
                            v-if="canEditDetails"
                            class="flex items-center gap-4"
                        >
                            <Button type="submit" :disabled="processing">
                                {{
                                    processing
                                        ? $t('common.saving')
                                        : $t('common.saveChanges')
                                }}
                            </Button>
                        </div>
                    </Form>
                </div>

                <!-- Sidebar -->
                <div class="space-y-4">
                    <!-- Status & Transition -->
                    <div
                        class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
                    >
                        <h3 class="mb-3 text-sm font-semibold">
                            {{ $t('competitions.sidebar.status') }}
                        </h3>
                        <Badge
                            :class="statusColor(competition.status)"
                            class="mb-3 capitalize"
                        >
                            {{ competition.status.replace(/_/g, ' ') }}
                        </Badge>
                        <div v-if="transition" class="mt-2">
                            <Button
                                size="sm"
                                class="w-full"
                                :variant="transition.variant as any"
                                :disabled="transitioning"
                                @click="submitTransition"
                            >
                                <Loader2
                                    v-if="transitioning"
                                    class="mr-1.5 size-4 animate-spin"
                                />
                                {{ transition.label }}
                            </Button>
                            <p
                                v-if="transition.confirm"
                                class="mt-2 text-[11px] text-muted-foreground"
                            >
                                <AlertTriangle class="mr-0.5 inline size-3" />
                                {{
                                    competition.status === 'running'
                                        ? $t('competitions.transition.willEnd')
                                        : $t(
                                              'competitions.transition.cannotUndo',
                                          )
                                }}
                            </p>
                        </div>
                    </div>

                    <!-- Teams -->
                    <div
                        class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
                    >
                        <div class="mb-3 flex items-center justify-between">
                            <h3 class="text-sm font-semibold">
                                {{ $t('competitions.sidebar.teams') }}
                            </h3>
                            <span class="text-xs text-muted-foreground">
                                {{ teamCount
                                }}<template v-if="maxTeams">
                                    / {{ maxTeams }}</template
                                >
                            </span>
                        </div>

                        <div v-if="maxTeams > 0" class="mb-3">
                            <div
                                class="h-2 w-full overflow-hidden rounded-full bg-muted"
                            >
                                <div
                                    class="h-full rounded-full bg-primary transition-all duration-500"
                                    :style="{ width: teamProgress + '%' }"
                                />
                            </div>
                        </div>

                        <div class="space-y-2">
                            <div
                                v-for="team in competition.teams"
                                :key="team.id"
                                class="rounded-lg border px-3 py-2 text-sm"
                            >
                                <div class="flex items-center justify-between">
                                    <span class="font-medium">
                                        {{ team.name }}
                                        <span
                                            v-if="team.tag"
                                            class="text-muted-foreground"
                                            >[{{ team.tag }}]</span
                                        >
                                    </span>
                                    <span
                                        class="flex items-center gap-1 text-xs text-muted-foreground"
                                    >
                                        <Users class="size-3" />
                                        {{ team.active_members?.length ?? 0 }}
                                    </span>
                                </div>
                                <div class="text-xs text-muted-foreground">
                                    {{
                                        $t('competitions.captainNamed', {
                                            name:
                                                team.captain?.name ??
                                                $t(
                                                    'competitions.sidebar.captainNone',
                                                ),
                                        })
                                    }}
                                </div>
                            </div>
                            <p
                                v-if="!competition.teams?.length"
                                class="text-xs text-muted-foreground"
                            >
                                {{ $t('competitions.sidebar.noTeams') }}
                            </p>
                        </div>
                    </div>

                    <!-- LanBrackets -->
                    <div
                        v-if="lanbracketsEnabled"
                        class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
                    >
                        <h3 class="mb-3 text-sm font-semibold">
                            {{ $t('competitions.sidebar.lanbrackets') }}
                        </h3>
                        <div class="space-y-2">
                            <template v-if="competition.lanbrackets_id">
                                <Badge
                                    variant="outline"
                                    class="border-green-300 text-green-700 dark:border-green-700 dark:text-green-400"
                                >
                                    <CheckCircle2 class="mr-1 size-3" />
                                    {{ $t('competitions.sidebar.synced') }}
                                </Badge>
                                <div class="mt-2 space-y-1.5">
                                    <a
                                        v-if="lanbracketsAdminUrl"
                                        :href="lanbracketsAdminUrl"
                                        target="_blank"
                                        rel="noopener"
                                        class="flex items-center gap-1 text-sm text-primary hover:underline"
                                    >
                                        <ExternalLink class="size-3" />
                                        {{ $t('competitions.sidebar.adminUi') }}
                                    </a>
                                    <a
                                        v-if="bracketViewUrl"
                                        :href="bracketViewUrl"
                                        target="_blank"
                                        rel="noopener"
                                        class="flex items-center gap-1 text-sm text-primary hover:underline"
                                    >
                                        <ExternalLink class="size-3" />
                                        {{
                                            $t(
                                                'competitions.sidebar.bracketView',
                                            )
                                        }}
                                    </a>
                                </div>
                            </template>
                            <p v-else class="text-xs text-muted-foreground">
                                {{ $t('competitions.sidebar.notSynced') }}
                            </p>
                        </div>
                    </div>

                    <!-- Competition info -->
                    <div
                        class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
                    >
                        <h3 class="mb-3 text-sm font-semibold">
                            {{ $t('competitions.sidebar.details') }}
                        </h3>
                        <dl class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <dt class="text-muted-foreground">
                                    {{ $t('competitions.form.type') }}
                                </dt>
                                <dd class="capitalize">
                                    {{ competition.type }}
                                </dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-muted-foreground">
                                    {{ $t('competitions.sidebar.format') }}
                                </dt>
                                <dd class="capitalize">
                                    {{
                                        competition.stage_type?.replace(
                                            /_/g,
                                            ' ',
                                        )
                                    }}
                                </dd>
                            </div>
                            <div
                                v-if="competition.game"
                                class="flex justify-between"
                            >
                                <dt class="text-muted-foreground">
                                    {{ $t('competitions.form.game') }}
                                </dt>
                                <dd>{{ competition.game.name }}</dd>
                            </div>
                            <div
                                v-if="competition.starts_at"
                                class="flex justify-between"
                            >
                                <dt class="text-muted-foreground">
                                    {{ $t('competitions.sidebar.starts') }}
                                </dt>
                                <dd class="text-xs">
                                    {{
                                        new Date(
                                            competition.starts_at,
                                        ).toLocaleString()
                                    }}
                                </dd>
                            </div>
                            <div
                                v-if="competition.ends_at"
                                class="flex justify-between"
                            >
                                <dt class="text-muted-foreground">
                                    {{ $t('competitions.sidebar.ends') }}
                                </dt>
                                <dd class="text-xs">
                                    {{
                                        new Date(
                                            competition.ends_at,
                                        ).toLocaleString()
                                    }}
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
