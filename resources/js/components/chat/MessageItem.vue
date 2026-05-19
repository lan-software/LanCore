<script setup lang="ts">
// @see docs/mil-std-498/SRS.md CHT-F-024, CHT-F-027, COMP-REF-001
import { ShieldCheck } from 'lucide-vue-next';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { Button } from '@/components/ui/button';
import type { ChatMessageDto } from './types';

const props = defineProps<{
    message: ChatMessageDto;
    canModerate: boolean;
    currentUserId: string | null;
    isReferee?: boolean;
}>();

const emit = defineEmits<{
    (e: 'delete', id: string): void;
}>();

const { t } = useI18n();

const isDeleted = computed(() => props.message.deleted_at !== null);
const isMentioned = computed(
    () =>
        props.currentUserId !== null &&
        props.message.mentions.includes(props.currentUserId),
);
const displayName = computed(
    () =>
        props.message.user?.name ??
        props.message.user?.username ??
        'Unknown',
);

function onDelete(): void {
    if (window.confirm(t('chat.message.deleteConfirm'))) {
        emit('delete', props.message.id);
    }
}
</script>

<template>
    <div
        class="group flex flex-col gap-1 rounded-md px-3 py-2 transition-colors"
        :class="[
            isMentioned
                ? 'bg-amber-50 dark:bg-amber-950/30'
                : 'hover:bg-zinc-50 dark:hover:bg-zinc-900/40',
        ]"
        :data-deleted="isDeleted ? 'true' : 'false'"
    >
        <div class="flex items-center gap-2 text-xs text-zinc-500 dark:text-zinc-400">
            <span class="font-semibold text-zinc-700 dark:text-zinc-200">{{ displayName }}</span>
            <span
                v-if="isReferee"
                class="inline-flex items-center gap-0.5 rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-amber-800 dark:bg-amber-900/40 dark:text-amber-300"
                :title="t('chat.message.refereeBadgeTitle')"
            >
                <ShieldCheck class="size-3" />
                {{ t('chat.message.refereeBadge') }}
            </span>
            <time v-if="message.created_at" :datetime="message.created_at">
                {{ new Date(message.created_at).toLocaleTimeString() }}
            </time>
        </div>
        <p
            v-if="isDeleted"
            class="text-sm italic text-zinc-500 dark:text-zinc-400"
        >
            {{ t('chat.message.deletedPlaceholder') }}
        </p>
        <p v-else class="whitespace-pre-wrap text-sm text-zinc-900 dark:text-zinc-100">
            {{ message.body }}
        </p>
        <div
            v-if="canModerate && !isDeleted"
            class="flex justify-end opacity-0 transition-opacity group-hover:opacity-100"
        >
            <Button
                type="button"
                variant="ghost"
                size="sm"
                @click="onDelete"
            >
                {{ t('chat.message.delete') }}
            </Button>
        </div>
    </div>
</template>
