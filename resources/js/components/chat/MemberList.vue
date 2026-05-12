<script setup lang="ts">
// @see docs/mil-std-498/SRS.md CHT-F-026
import ChatModerationController from '@/actions/App/Domain/Chat/Http/Controllers/ChatModerationController';
import PresenceIndicator from '@/components/PresenceIndicator.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { PresenceStatus } from '@/composables/usePresence';
import { Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import type { ChatMemberDto, MemberPresenceMap } from './types';

const props = defineProps<{
    roomId: number;
    members: ChatMemberDto[];
    presence: MemberPresenceMap;
    canModerate: boolean;
}>();

const { t } = useI18n();

function statusFor(userId: number): PresenceStatus {
    return props.presence[userId] ?? 'offline';
}

function muteFor(member: ChatMemberDto): void {
    const reason = window.prompt(t('chat.moderation.reason') ?? '') ?? '';
    router.post(
        ChatModerationController.mute({
            room: props.roomId,
            user: member.user_id,
        }).url,
        { minutes: 60, reason },
        { preserveScroll: true },
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

function isMuted(member: ChatMemberDto): boolean {
    if (!member.muted_until) return false;
    return new Date(member.muted_until).getTime() > Date.now();
}
</script>

<template>
    <aside class="flex h-full flex-col gap-2 p-3">
        <h2 class="text-sm font-semibold text-zinc-700 dark:text-zinc-200">
            {{ t('chat.room.members') }}
            <span class="ml-1 text-xs text-zinc-500">({{ members.length }})</span>
        </h2>
        <ul class="flex flex-col gap-1 overflow-y-auto">
            <li
                v-for="member in members"
                :key="member.user_id"
                class="flex items-center gap-2 rounded-md px-2 py-1.5 hover:bg-zinc-50 dark:hover:bg-zinc-900"
            >
                <PresenceIndicator :status="statusFor(member.user_id)" size="sm" />
                <component
                    :is="member.username ? Link : 'div'"
                    v-bind="
                        member.username
                            ? {
                                  href: `/u/${member.username}`,
                                  class: 'flex-1 min-w-0 truncate text-sm rounded-sm hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-ring',
                              }
                            : { class: 'flex-1 min-w-0 truncate text-sm' }
                    "
                >
                    <div class="flex flex-wrap items-center gap-1">
                        <span class="font-medium">{{ member.name ?? member.username }}</span>
                        <Badge
                            v-if="member.is_admin"
                            variant="default"
                            class="px-1.5 py-0 text-[10px] uppercase tracking-wide"
                            :title="t('chat.member.adminTitle')"
                        >
                            {{ t('chat.member.adminBadge') }}
                        </Badge>
                        <Badge
                            v-if="member.team_name"
                            variant="secondary"
                            class="px-1.5 py-0 text-[10px]"
                            :title="member.team_name ?? undefined"
                        >
                            {{ member.team_tag ?? member.team_name }}
                        </Badge>
                    </div>
                    <span v-if="member.username" class="block truncate text-xs text-zinc-500">
                        @{{ member.username }}
                    </span>
                </component>
                <template v-if="canModerate">
                    <Button
                        v-if="!isMuted(member)"
                        type="button"
                        variant="ghost"
                        size="sm"
                        @click="muteFor(member)"
                    >
                        {{ t('chat.moderation.mute') }}
                    </Button>
                    <Button
                        v-else
                        type="button"
                        variant="outline"
                        size="sm"
                        @click="unmuteFor(member)"
                    >
                        {{ t('chat.moderation.unmute') }}
                    </Button>
                </template>
            </li>
        </ul>
    </aside>
</template>
