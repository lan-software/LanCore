<script setup lang="ts">
// @see docs/mil-std-498/SRS.md CHT-F-026
import ChatRoom from '@/components/chat/ChatRoom.vue';
import type {
    ChatMemberDto,
    ChatMessageDto,
    ChatRoomDto,
    MemberPresenceMap,
} from '@/components/chat/types';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps<{
    room: ChatRoomDto;
    messages: ChatMessageDto[];
    members: ChatMemberDto[];
    memberPresence: MemberPresenceMap;
}>();

const { t } = useI18n();
const title = computed(() => props.room.title ?? t('chat.room.title'));

const breadcrumbs = computed(() => [
    { title: t('chat.room.title'), href: '#' },
    { title: title.value, href: '#' },
]);
</script>

<template>
    <Head :title="title" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-[calc(100vh-6rem)] flex-col">
            <ChatRoom
                :room="room"
                :messages="messages"
                :members="members"
                :member-presence="memberPresence"
            />
        </div>
    </AppLayout>
</template>
