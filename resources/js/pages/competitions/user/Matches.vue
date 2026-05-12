<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { MessageCircle, Swords } from 'lucide-vue-next';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import UserCompetitionController from '@/actions/App/Domain/Competition/Http/Controllers/UserCompetitionController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as myCompetitionsRoute } from '@/routes/my-competitions';
import type { BreadcrumbItem } from '@/types';

interface MatchParticipant {
    participant_id: number;
    team_id: number | null;
    team_name: string | null;
    score: number | null;
    result: string | null;
}

interface Match {
    id: number;
    round_number: number | null;
    sequence: number | null;
    status: string | null;
    participants: MatchParticipant[];
    user_is_participant: boolean;
    chat_room_id: number | null;
    chat_room_status: 'open' | 'write_locked' | 'archived' | null;
}

interface Stage {
    id: number;
    name: string | null;
    stage_type: string | null;
    status: string | null;
    matches: Match[];
}

const { t } = useI18n();

const props = defineProps<{
    competition: { id: number; name: string };
    userTeam: { id: number; name: string } | null;
    stages: Stage[];
}>();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: t('navigation.myCompetitions'), href: myCompetitionsRoute().url },
    {
        title: props.competition.name,
        href: UserCompetitionController.show(props.competition.id).url,
    },
    {
        title: t('competitions.user.matchesTitle'),
        href: `/portal/competitions/${props.competition.id}/matches`,
    },
]);

const hasAnyMatch = computed(() =>
    props.stages.some((stage) => stage.matches.length > 0),
);

function openChat(match: Match): void {
    if (match.chat_room_id) {
        router.visit(`/chat/rooms/${match.chat_room_id}`);

        return;
    }

    router.post(
        `/portal/competitions/${props.competition.id}/matches/${match.id}/open-chat`,
        {},
        { preserveScroll: true },
    );
}
</script>

<template>
    <Head :title="t('competitions.user.matchesTitle')" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-4">
            <header class="flex items-center gap-2">
                <Swords class="size-5 text-primary" />
                <h1 class="text-xl font-semibold">
                    {{ competition.name }} — {{ t('competitions.user.matchesTitle') }}
                </h1>
            </header>

            <p
                v-if="!hasAnyMatch"
                class="rounded-md border border-dashed border-sidebar-border/70 p-6 text-center text-sm text-muted-foreground"
            >
                {{ t('competitions.user.matchesEmpty') }}
            </p>

            <section
                v-for="stage in stages"
                :key="stage.id"
                class="space-y-3"
            >
                <h2 class="text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                    {{ stage.name ?? `Stage ${stage.id}` }}
                    <Badge
                        v-if="stage.status"
                        variant="outline"
                        class="ml-2"
                    >
                        {{ stage.status }}
                    </Badge>
                </h2>

                <ul class="space-y-2">
                    <li
                        v-for="match in stage.matches"
                        :key="match.id"
                        class="flex flex-col gap-3 rounded-lg border border-sidebar-border/70 p-3 md:flex-row md:items-center md:justify-between"
                        :class="match.user_is_participant ? 'border-primary/50 bg-primary/5' : ''"
                    >
                        <div class="flex-1">
                            <div class="flex items-center gap-2 text-xs text-muted-foreground">
                                <span>
                                    {{ t('competitions.user.round') }}
                                    {{ match.round_number ?? '?' }}
                                </span>
                                <span v-if="match.sequence !== null">
                                    · {{ t('competitions.user.matchNumber', { n: match.sequence }) }}
                                </span>
                                <Badge
                                    v-if="match.status"
                                    variant="secondary"
                                    class="ml-1"
                                >
                                    {{ match.status }}
                                </Badge>
                            </div>
                            <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm">
                                <template
                                    v-for="(p, idx) in match.participants"
                                    :key="`${match.id}-${idx}`"
                                >
                                    <span class="font-medium">
                                        {{ p.team_name ?? t('competitions.user.tbd') }}
                                    </span>
                                    <span
                                        v-if="p.score !== null"
                                        class="font-mono text-muted-foreground"
                                    >
                                        ({{ p.score }})
                                    </span>
                                    <span
                                        v-if="idx < match.participants.length - 1"
                                        class="text-muted-foreground"
                                    >
                                        vs
                                    </span>
                                </template>
                                <span
                                    v-if="match.participants.length === 0"
                                    class="text-muted-foreground"
                                >
                                    {{ t('competitions.user.tbd') }}
                                </span>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <Button
                                v-if="match.user_is_participant"
                                size="sm"
                                variant="outline"
                                @click="openChat(match)"
                            >
                                <MessageCircle class="mr-1 size-4" />
                                {{
                                    match.chat_room_id
                                        ? t('competitions.user.openChat')
                                        : t('competitions.user.startMatchChat')
                                }}
                            </Button>
                        </div>
                    </li>
                </ul>
            </section>
        </div>
    </AppLayout>
</template>
