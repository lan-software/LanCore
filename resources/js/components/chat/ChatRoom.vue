<script setup lang="ts">
// @see docs/mil-std-498/SRS.md CHT-F-026
// @see docs/mil-std-498/IDD.md §3.14
import { router, usePage } from '@inertiajs/vue3';
import { useEchoPresence } from '@laravel/echo-vue';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
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
import MemberList from './MemberList.vue';
import MessageComposer from './MessageComposer.vue';
import MessageList from './MessageList.vue';
import type {
    ChatMemberDto,
    ChatMessageDto,
    ChatRoomDto,
    MemberPresenceMap,
} from './types';

const props = withDefaults(
    defineProps<{
        room: ChatRoomDto;
        messages: ChatMessageDto[];
        members: ChatMemberDto[];
        memberPresence: MemberPresenceMap;
        /**
         * Insert a transient "observer" membership for the duration the component
         * is mounted. Used by admin pages where the admin should appear in the
         * member list while actively viewing the chat, then disappear on leave.
         */
        observerMode?: boolean;
    }>(),
    { observerMode: false },
);

const { t } = useI18n();
const page = usePage();
const currentUserId = computed<string | null>(
    () =>
        (page.props.auth as { user?: { id?: string } } | undefined)?.user?.id ??
        null,
);

const liveMessages = ref<ChatMessageDto[]>([...props.messages]);
const hasMore = ref(props.messages.length >= 50);
const loadingHistory = ref(false);
const memberSheetOpen = ref(false);

// Derive referee user IDs from the annotated member list so MessageItem can
// show the referee badge next to messages authored by them.
// @see docs/mil-std-498/SRS.md COMP-REF-001
const refereeUserIds = computed<string[]>(() =>
    props.members.filter((m) => m.is_referee).map((m) => m.user_id),
);

interface BroadcastPayload {
    id: string;
    room_id: string;
    user_id: string | null;
    body: string;
    mentions: number[];
    created_at: string;
}

interface PresenceMember {
    id: string;
    name: string | null;
    username: string | null;
}

const presentUserIds = ref<Set<number>>(new Set());

const { channel: getPresenceChannel } = useEchoPresence<BroadcastPayload>(
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

// Track who else is currently subscribed to this presence channel. Drives the
// green-dot-with-checkmark overlay on the indicator (SRS CHT-F-036). Register
// synchronously (NOT in onMounted) so the callbacks are wired before Pusher's
// async `subscription_succeeded` resolves and fires the initial `here` event.
const presenceChannel = getPresenceChannel();
presenceChannel
    .here((members: PresenceMember[]) => {
         
        console.info('[ChatRoom] presence here', members);
        presentUserIds.value = new Set(members.map((m) => m.id));
    })
    .joining((member: PresenceMember) => {
         
        console.info('[ChatRoom] presence joining', member);
        const next = new Set(presentUserIds.value);
        next.add(member.id);
        presentUserIds.value = next;
    })
    .leaving((member: PresenceMember) => {
         
        console.info('[ChatRoom] presence leaving', member);
        const next = new Set(presentUserIds.value);
        next.delete(member.id);
        presentUserIds.value = next;
    });

function observerJoinUrl(): string {
    return `/chat/rooms/${props.room.id}/observer`;
}

function leaveObserverViaBeacon(): void {
    if (!props.observerMode) {
return;
}

    // DELETE via fetch with keepalive so the request still completes during
    // page unload. sendBeacon only does POST, so we fall back to fetch.
    try {
        fetch(observerJoinUrl(), {
            method: 'DELETE',
            credentials: 'same-origin',
            keepalive: true,
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN':
                    document
                        .querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
                        ?.getAttribute('content') ?? '',
            },
        });
    } catch {
        // Best-effort cleanup; if the browser blocks the unload request the
        // membership stays until the next observer join (idempotent) or
        // pruning sweep.
    }
}

onMounted(async () => {
    if (!props.observerMode) {
return;
}

    try {
        await fetch(observerJoinUrl(), {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN':
                    document
                        .querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
                        ?.getAttribute('content') ?? '',
            },
        });
    } catch {
        // Best-effort; the policy already permits the admin to view, so missing
        // the membership row only hides them from the member list.
    }

    window.addEventListener('pagehide', leaveObserverViaBeacon);
    window.addEventListener('beforeunload', leaveObserverViaBeacon);
});

onBeforeUnmount(() => {
    leaveObserverViaBeacon();
    window.removeEventListener('pagehide', leaveObserverViaBeacon);
    window.removeEventListener('beforeunload', leaveObserverViaBeacon);
});

async function onLoadMore(): Promise<void> {
    const oldest = liveMessages.value[0];

    if (!oldest || loadingHistory.value) {
return;
}

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

function onDeleteMessage(messageId: string): void {
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
    if (!window.confirm(t('chat.moderation.close'))) {
return;
}

    router.post(
        ChatModerationController.close({ room: props.room.id }).url,
        {},
        { preserveScroll: true },
    );
}

function reopenRoom(): void {
    if (!window.confirm(t('chat.moderation.reopen'))) {
return;
}

    router.post(
        ChatModerationController.reopen({ room: props.room.id }).url,
        {},
        { preserveScroll: true },
    );
}

const disabledReason = computed<string | null>(() => {
    if (props.room.is_archived) {
return t('chat.room.archived');
}

    if (props.room.is_write_locked) {
return t('chat.room.writeLocked');
}

    if (!props.room.can_post) {
return t('chat.room.writeLocked');
}

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
                    <Button
                        v-if="room.can_moderate && !room.is_open"
                        type="button"
                        variant="outline"
                        size="sm"
                        @click="reopenRoom"
                    >
                        {{ t('chat.moderation.reopen') }}
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
                    :referee-user-ids="refereeUserIds"
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
                :present-user-ids="presentUserIds"
                :can-moderate="room.can_moderate"
            />
        </aside>
    </div>
</template>
