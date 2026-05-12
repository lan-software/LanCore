// @see docs/mil-std-498/IDD.md §3.14
import type { PresenceStatus } from '@/composables/usePresence';

export type RoomStatus = 'open' | 'write_locked' | 'archived';

export interface ChatUserRef {
    id: number;
    name: string | null;
    username: string | null;
}

export interface ChatMessageDto {
    id: number;
    room_id: number;
    user_id: number | null;
    user: ChatUserRef | null;
    body: string;
    mentions: number[];
    deleted_at: string | null;
    created_at: string | null;
}

export interface ChatMemberDto {
    user_id: number;
    name: string | null;
    username: string | null;
    role: string | null;
    muted_until: string | null;
}

export interface ChatRoomDto {
    id: number;
    key: string;
    title: string | null;
    status: RoomStatus;
    can_post: boolean;
    can_moderate: boolean;
    is_open: boolean;
    is_archived: boolean;
    is_write_locked: boolean;
}

export type MemberPresenceMap = Record<number, PresenceStatus>;
