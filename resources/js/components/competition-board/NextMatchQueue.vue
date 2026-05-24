<script setup lang="ts">
import { useI18n } from 'vue-i18n';
import { Badge } from '@/components/ui/badge';
import type { MatchProposalDto } from './types';

defineProps<{
    proposals: MatchProposalDto[];
}>();

const { t } = useI18n();
</script>

<template>
    <div
        class="border-t border-zinc-200 bg-zinc-50 p-3 dark:border-zinc-800 dark:bg-zinc-900"
    >
        <div class="mb-2 text-sm font-semibold">
            {{ t('competition_board.proposal.title') }}
        </div>
        <div v-if="proposals.length === 0" class="text-xs text-zinc-500">
            {{ t('competition_board.proposal.empty') }}
        </div>
        <ul v-else class="space-y-2">
            <li
                v-for="p in proposals"
                :key="String(p.match_id) + '-' + p.competition_id"
                class="rounded border bg-white p-2 dark:bg-zinc-950"
                :class="
                    p.blocked
                        ? 'border-rose-300 opacity-70'
                        : 'border-zinc-200 dark:border-zinc-800'
                "
            >
                <div class="flex items-center justify-between text-xs">
                    <div class="font-medium">{{ p.competition_name }}</div>
                    <Badge :variant="p.blocked ? 'destructive' : 'secondary'">{{
                        p.stage_name
                    }}</Badge>
                </div>
                <div
                    class="mt-1 truncate text-[11px] text-zinc-600 dark:text-zinc-300"
                >
                    <template v-for="(part, idx) in p.participants" :key="idx">
                        <span>{{ part.name || '?' }}</span>
                        <span v-if="idx < p.participants.length - 1"> vs </span>
                    </template>
                </div>
                <div class="mt-1 flex flex-wrap gap-1">
                    <span
                        v-for="key in p.reason_keys"
                        :key="key"
                        class="rounded bg-zinc-100 px-1 py-0.5 text-[10px] dark:bg-zinc-800"
                    >
                        {{ t(key) }}
                    </span>
                </div>
            </li>
        </ul>
    </div>
</template>
