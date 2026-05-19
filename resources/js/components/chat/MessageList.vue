<script setup lang="ts">
// @see docs/mil-std-498/SRS.md CHT-F-026, CHT-F-027
import { nextTick, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import MessageItem from './MessageItem.vue';
import type { ChatMessageDto } from './types';

const props = defineProps<{
    messages: ChatMessageDto[];
    canModerate: boolean;
    currentUserId: string | null;
    hasMore: boolean;
    loading?: boolean;
    refereeUserIds?: string[];
}>();

const emit = defineEmits<{
    (e: 'load-more'): void;
    (e: 'delete-message', id: string): void;
}>();

const { t } = useI18n();
const scroller = ref<HTMLElement | null>(null);

watch(
    () => props.messages.length,
    async (newLen, oldLen) => {
        if (!scroller.value) {
            return;
        }

        // Only auto-scroll if a new message was appended at the bottom
        if (oldLen === 0 || newLen > oldLen) {
            await nextTick();
            scroller.value.scrollTop = scroller.value.scrollHeight;
        }
    },
    { flush: 'post' },
);
</script>

<template>
    <div
        ref="scroller"
        class="flex h-full flex-col gap-1 overflow-y-auto p-3"
        role="log"
        aria-live="polite"
    >
        <div v-if="hasMore" class="flex justify-center py-2">
            <Button
                type="button"
                variant="outline"
                size="sm"
                :disabled="loading"
                @click="emit('load-more')"
            >
                <Spinner v-if="loading" class="mr-2 h-3 w-3" />
                {{ t('chat.message.loadMore') }}
            </Button>
        </div>
        <p
            v-if="messages.length === 0"
            class="my-auto text-center text-sm text-zinc-500 dark:text-zinc-400"
        >
            {{ t('chat.room.empty') }}
        </p>
        <MessageItem
            v-for="message in messages"
            :key="message.id"
            :message="message"
            :can-moderate="canModerate"
            :current-user-id="currentUserId"
            :is-referee="message.user_id !== null && (refereeUserIds ?? []).includes(message.user_id)"
            @delete="emit('delete-message', $event)"
        />
    </div>
</template>
