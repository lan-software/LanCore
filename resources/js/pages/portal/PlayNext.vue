<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import type { MatchProposalDto } from '@/components/competition-board/types';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';

const props = defineProps<{
    event: { id: string; name: string } | null;
    proposals: MatchProposalDto[];
}>();

const { t } = useI18n();

const top = props.proposals[0] ?? null;
const upcoming = props.proposals.slice(1, 3);
</script>

<template>
    <AppLayout>
        <Head :title="t('play_next.title')" />
        <div class="space-y-4 p-4">
            <Heading :title="t('play_next.title')" :description="event?.name ?? t('play_next.no_event')" />

            <Card v-if="top">
                <CardContent class="space-y-3 p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div class="text-lg font-semibold">{{ top.competition_name }}</div>
                            <div class="text-sm text-zinc-500">{{ top.stage_name }}</div>
                        </div>
                        <Badge :variant="top.blocked ? 'destructive' : 'secondary'">
                            {{ top.blocked ? t('play_next.blocked') : t('play_next.ready') }}
                        </Badge>
                    </div>

                    <div class="text-sm">
                        <template v-for="(part, idx) in top.participants" :key="idx">
                            <span>{{ part.name || '?' }}</span>
                            <span v-if="idx < top.participants.length - 1"> vs </span>
                        </template>
                    </div>

                    <div class="flex flex-wrap gap-1">
                        <span
                            v-for="key in top.reason_keys"
                            :key="key"
                            class="rounded bg-zinc-100 px-2 py-0.5 text-xs dark:bg-zinc-800"
                        >
                            {{ t(key) }}
                        </span>
                    </div>
                </CardContent>
            </Card>

            <div v-else class="rounded border border-dashed p-6 text-center text-sm text-zinc-500">
                {{ t('play_next.empty') }}
            </div>

            <div v-if="upcoming.length > 0">
                <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-zinc-500">
                    {{ t('play_next.and_after') }}
                </div>
                <ul class="space-y-2">
                    <li
                        v-for="p in upcoming"
                        :key="String(p.match_id) + '-' + p.competition_id"
                        class="rounded border bg-white p-2 text-sm dark:bg-zinc-950"
                    >
                        <div class="flex items-center justify-between">
                            <span class="font-medium">{{ p.competition_name }}</span>
                            <Badge variant="secondary">{{ p.stage_name }}</Badge>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </AppLayout>
</template>
