<script setup lang="ts">
// @see docs/mil-std-498/SRS.md CHT-F-026
// @see docs/mil-std-498/IDD.md §3.14
import ChatMessageController from '@/actions/App/Domain/Chat/Http/Controllers/ChatMessageController';
import ChatModerationController from '@/actions/App/Domain/Chat/Http/Controllers/ChatModerationController';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { router, usePage } from '@inertiajs/vue3';
import { useEcho } from '@laravel/echo-vue';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import MemberList from './MemberList.vue';
import MessageComposer from './MessageComposer.vue';
import MessageList from './MessageList.vue';
import type {
    ChatMemberDto,
    ChatMessageDto,
    ChatRoomDto,
    MemberPresenceMap,
} from './types';

const props = defineProps<{
    room: ChatRoomDto;
    messages: ChatMessageDto[];
    members: ChatMemberDto[];
    memberPresence: MemberPresenceMap;
}>();

const { t } = useI18n();
const page = usePage();
const currentUserId = computed<number | null>(
    () =>
        (page.props.auth as { user?: { id?: number } } | undefined)?.user?.id ??
        null,
);

const liveMessages = ref<ChatMessageDto[]>([...props.messages]);
const hasMore = ref(props.messages.length >= 50);
const loadingHistory = ref(false);
const memberSheetOpen = ref(false);

interface BroadcastPayload {
    id: number;
    room_id: number;
    user_id: number | null;
    body: string;
    mentions: number[];
    created_at: string;
}

useEcho<BroadcastPayload>(
    `chat.room.${props.room.id}`,
    '.message.posted',
    (payload) => {
        if (liveMessages.value.some((m) => m.id === payload.id)) {
            return;
        }
        const member = props.members.find(
            (m) => m.user_id === payload.user_id,
        );
        liveMessages.value.push({
            id: payload.id,
            room_id: payload.room_id,
            user_id: payload.user_id,
            user: member
                ? {
                      id: member.user_id,
                      name: member.name,
                      username: member.username,
                  }
                : { id: payload.user_id ?? 0, name: null, username: null },
            body: payload.body,
            mentions: payload.mentions ?? [],
            deleted_at: null,
            created_at: payload.created_at,
        });
    },
    [props.room.id],
);

async function onLoadMore(): Promise<void> {
    const oldest = liveMessages.value[0];
    if (!oldest || loadingHistory.value) return;
    loadingHistory.value = true;
    try {
        const url =
            ChatMessageController.index({ room: props.room.id }).url +
            `?before=${oldest.id}&limit=25`;
        const res = await fetch(url, {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        });
        if (res.ok) {
            const data = (await res.json()) as {
                messages: ChatMessageDto[];
                has_more: boolean;
            };
            liveMessages.value = [...data.messages, ...liveMessages.value];
            hasMore.value = data.has_more;
        }
    } finally {
        loadingHistory.value = false;
    }
}

function onDeleteMessage(messageId: number): void {
    router.delete(
        ChatMessageController.destroy({
            room: props.room.id,
            message: messageId,
        }).url,
        {
            preserveScroll: true,
            onSuccess: () => {
                const idx = liveMessages.value.findIndex(
                    (m) => m.id === messageId,
                );
                if (idx >= 0) {
                    liveMessages.value[idx] = {
                        ...liveMessages.value[idx],
                        deleted_at: new Date().toISOString(),
                    };
                }
            },
        },
    );
}

function closeRoom(): void {
    if (!window.confirm(t('chat.moderation.close'))) return;
    router.post(
        ChatModerationController.close({ room: props.room.id }).url,
        {},
        { preserveScroll: true },
    );
}

const disabledReason = computed<string | null>(() => {
    if (props.room.is_archived) return t('chat.room.archived');
    if (props.room.is_write_locked) return t('chat.room.writeLocked');
    if (!props.room.can_post) return t('chat.room.writeLocked');
    return null;
});
</script>

<template>
    <div class="flex h-full min-h-0 flex-col md:flex-row">
        <section class="flex min-h-0 flex-1 flex-col">
            <header
                class="flex items-center justify-between border-b border-zinc-200 px-3 py-2 dark:border-zinc-800"
            >
                <h1 class="text-base font-semibold">
                    {{ room.title ?? t('chat.room.title') }}
                </h1>
                <div class="flex items-center gap-2">
                    <Button
                        v-if="room.can_moderate && room.is_open"
                        type="button"
                        variant="outline"
                        size="sm"
                        @click="closeRoom"
                    >
                        {{ t('chat.moderation.close') }}
                    </Button>
                    <Sheet v-model:open="memberSheetOpen">
                        <SheetTrigger as-child>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                class="md:hidden"
                            >
                                {{ t('chat.room.membersToggle') }}
                            </Button>
                        </SheetTrigger>
                        <SheetContent side="right" class="w-72 p-0">
                            <SheetHeader>
                                <SheetTitle>{{ t('chat.room.members') }}</SheetTitle>
                            </SheetHeader>
                            <MemberList
                                :room-id="room.id"
                                :members="members"
                                :presence="memberPresence"
                                :can-moderate="room.can_moderate"
                            />
                        </SheetContent>
                    </Sheet>
                </div>
            </header>
            <div class="min-h-0 flex-1">
                <MessageList
                    :messages="liveMessages"
                    :can-moderate="room.can_moderate"
                    :current-user-id="currentUserId"
                    :has-more="hasMore"
                    :loading="loadingHistory"
                    @load-more="onLoadMore"
                    @delete-message="onDeleteMessage"
                />
            </div>
            <MessageComposer
                :room-id="room.id"
                :disabled="!room.can_post"
                :disabled-reason="disabledReason"
            />
        </section>
        <aside
            class="hidden w-64 border-l border-zinc-200 dark:border-zinc-800 md:block"
        >
            <MemberList
                :room-id="room.id"
                :members="members"
                :presence="memberPresence"
                :can-moderate="room.can_moderate"
            />
        </aside>
    </div>
</template>
