<script setup lang="ts">
// @see docs/mil-std-498/SRS.md CHT-F-026
import { Link, router } from '@inertiajs/vue3';
import { useEchoPresence } from '@laravel/echo-vue';
import { ShieldCheck, Volume2, VolumeX } from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import ChatModerationController from '@/actions/App/Domain/Chat/Http/Controllers/ChatModerationController';
import PresenceIndicator from '@/components/PresenceIndicator.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import type { PresenceStatus } from '@/composables/usePresence';
import type { ChatMemberDto, MemberPresenceMap } from './types';

const props = withDefaults(
    defineProps<{
        roomId: number;
        members: ChatMemberDto[];
        presence: MemberPresenceMap;
        canModerate: boolean;
        /**
         * Set of user ids currently subscribed to the room's presence channel —
         * i.e. actively viewing this chat. Drives the green-with-check overlay.
         */
        presentUserIds?: Set<number>;
    }>(),
    { presentUserIds: () => new Set<number>() },
);

const { t } = useI18n();

// Mirror server-rendered presence into a local ref so live `presence.changed`
// broadcasts can update the indicator without a page reload (SRS PRS-F-014).
const livePresence = ref<MemberPresenceMap>({ ...props.presence });

watch(
    () => props.presence,
    (next) => {
        livePresence.value = { ...next };
    },
);

interface PresenceChangedPayload {
    user_id: number;
    status: PresenceStatus;
}

useEchoPresence<PresenceChangedPayload>(
    `chat.room.${props.roomId}`,
    '.presence.changed',
    (payload) => {
        livePresence.value = {
            ...livePresence.value,
            [payload.user_id]: payload.status,
        };
    },
    [props.roomId],
);

function statusFor(userId: number): PresenceStatus {
    return livePresence.value[userId] ?? 'offline';
}

function isInChat(userId: number): boolean {
    return props.presentUserIds.has(userId);
}

// Ticking "now" — re-renders the remaining-mute label every 30s so the
// countdown stays close to real-time without burning a high-rate timer.
const now = ref<number>(Date.now());
let nowInterval: ReturnType<typeof setInterval> | null = null;
onMounted(() => {
    nowInterval = setInterval(() => {
        now.value = Date.now();
    }, 30_000);
});
onBeforeUnmount(() => {
    if (nowInterval) clearInterval(nowInterval);
});

function isMuted(member: ChatMemberDto): boolean {
    if (!member.muted_until) return false;
    return new Date(member.muted_until).getTime() > now.value;
}

function muteRemainingLabel(member: ChatMemberDto): string {
    if (!member.muted_until) return '';
    const ms = new Date(member.muted_until).getTime() - now.value;
    if (ms <= 0) return '';
    const totalMinutes = Math.ceil(ms / 60_000);
    if (totalMinutes < 60) return `${totalMinutes}m`;
    const hours = Math.floor(totalMinutes / 60);
    const minutes = totalMinutes % 60;
    return minutes === 0 ? `${hours}h` : `${hours}h ${minutes}m`;
}

// --- Mute dialog state ----------------------------------------------------
const muteOpen = ref(false);
const muteTarget = ref<ChatMemberDto | null>(null);
const muteMinutes = ref<number>(60);
const muteReason = ref<string>('');
const muteSubmitting = ref(false);

const muteTitle = computed(() => {
    const handle = muteTarget.value?.username
        ? `@${muteTarget.value.username}`
        : (muteTarget.value?.name ?? '');
    return t('chat.moderation.muteModal.title', { user: handle });
});

function openMuteDialog(member: ChatMemberDto): void {
    muteTarget.value = member;
    muteMinutes.value = 60;
    muteReason.value = '';
    muteOpen.value = true;
}

function submitMute(): void {
    if (!muteTarget.value) return;
    const minutes = Math.max(1, Math.min(1440, Number(muteMinutes.value) || 0));
    muteSubmitting.value = true;
    router.post(
        ChatModerationController.mute({
            room: props.roomId,
            user: muteTarget.value.user_id,
        }).url,
        { minutes, reason: muteReason.value },
        {
            preserveScroll: true,
            onFinish: () => {
                muteSubmitting.value = false;
                muteOpen.value = false;
                muteTarget.value = null;
            },
        },
    );
}

function unmuteFor(member: ChatMemberDto): void {
    router.post(
        ChatModerationController.unmute({
            room: props.roomId,
            user: member.user_id,
        }).url,
        {},
        { preserveScroll: true },
    );
}
</script>

<template>
    <aside class="flex h-full flex-col gap-2 p-3">
        <h2 class="text-sm font-semibold text-zinc-700 dark:text-zinc-200">
            {{ t('chat.room.members') }}
            <span class="ml-1 text-xs text-zinc-500">({{ members.length }})</span>
        </h2>
        <ul class="flex flex-col gap-0.5 overflow-y-auto">
            <li
                v-for="member in members"
                :key="member.user_id"
                class="flex items-center gap-2 rounded-md px-2 py-1 hover:bg-zinc-50 dark:hover:bg-zinc-900"
            >
                <PresenceIndicator
                    :status="statusFor(member.user_id)"
                    :in-chat="isInChat(member.user_id)"
                    size="sm"
                />
                <component
                    :is="member.username ? Link : 'span'"
                    v-bind="
                        member.username
                            ? {
                                  href: `/u/${member.username}`,
                                  class: [
                                      'truncate text-sm font-medium rounded-sm hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-ring',
                                      isMuted(member)
                                          ? 'text-red-600 dark:text-red-400'
                                          : '',
                                  ],
                              }
                            : {
                                  class: [
                                      'truncate text-sm font-medium',
                                      isMuted(member)
                                          ? 'text-red-600 dark:text-red-400'
                                          : '',
                                  ],
                              }
                    "
                >
                    <span v-if="member.username">@{{ member.username }}</span>
                    <span v-else>{{ member.name }}</span>
                </component>
                <span
                    v-if="isMuted(member)"
                    class="shrink-0 rounded-sm bg-red-100 px-1 text-[10px] font-semibold uppercase tracking-wide text-red-700 dark:bg-red-950/60 dark:text-red-300"
                    :title="t('chat.moderation.mutedRemaining')"
                >
                    {{ muteRemainingLabel(member) }}
                </span>
                <ShieldCheck
                    v-if="member.is_admin"
                    class="size-4 shrink-0 text-primary"
                    :aria-label="t('chat.member.adminTitle')"
                />
                <span
                    v-if="member.team_tag"
                    class="shrink-0 rounded-sm bg-zinc-100 px-1 text-[10px] font-semibold uppercase tracking-wide text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300"
                    :title="member.team_name ?? undefined"
                >
                    {{ member.team_tag }}
                </span>
                <span
                    v-else-if="member.team_name"
                    class="shrink-0 truncate text-xs text-zinc-500 dark:text-zinc-400"
                    :title="member.team_name"
                >
                    {{ member.team_name }}
                </span>
                <span class="ml-auto" />
                <template v-if="canModerate">
                    <Button
                        v-if="!isMuted(member)"
                        type="button"
                        variant="ghost"
                        size="icon"
                        class="size-7 shrink-0 text-zinc-500 hover:text-foreground"
                        :title="t('chat.moderation.mute')"
                        :aria-label="t('chat.moderation.mute')"
                        @click="openMuteDialog(member)"
                    >
                        <VolumeX class="size-4" />
                    </Button>
                    <Button
                        v-else
                        type="button"
                        variant="ghost"
                        size="icon"
                        class="size-7 shrink-0 text-amber-600 hover:text-amber-700 dark:text-amber-400"
                        :title="t('chat.moderation.unmute')"
                        :aria-label="t('chat.moderation.unmute')"
                        @click="unmuteFor(member)"
                    >
                        <Volume2 class="size-4" />
                    </Button>
                </template>
            </li>
        </ul>

        <Dialog v-model:open="muteOpen">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>{{ muteTitle }}</DialogTitle>
                    <DialogDescription>
                        {{ t('chat.moderation.muteModal.description') }}
                    </DialogDescription>
                </DialogHeader>
                <form class="flex flex-col gap-3" @submit.prevent="submitMute">
                    <div class="flex flex-col gap-1">
                        <Label for="mute-minutes">
                            {{ t('chat.moderation.minutes') }}
                        </Label>
                        <Input
                            id="mute-minutes"
                            v-model.number="muteMinutes"
                            type="number"
                            min="1"
                            max="1440"
                            required
                        />
                    </div>
                    <div class="flex flex-col gap-1">
                        <Label for="mute-reason">
                            {{ t('chat.moderation.reason') }}
                        </Label>
                        <Textarea
                            id="mute-reason"
                            v-model="muteReason"
                            rows="3"
                            :placeholder="t('chat.moderation.muteModal.reasonPlaceholder')"
                        />
                        <p class="text-xs text-muted-foreground">
                            {{ t('chat.moderation.muteModal.reasonHint') }}
                        </p>
                    </div>
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            :disabled="muteSubmitting"
                            @click="muteOpen = false"
                        >
                            {{ t('common.cancel') }}
                        </Button>
                        <Button type="submit" :disabled="muteSubmitting">
                            {{ t('chat.moderation.mute') }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </aside>
</template>
