// @see docs/mil-std-498/IDD.md §3.14
import type { PresenceStatus } from '@/composables/usePresence';

export type RoomStatus = 'open' | 'write_locked' | 'archived';

export interface ChatUserRef {
    id: string;
    name: string | null;
    username: string | null;
}

export interface ChatMessageDto {
    id: string;
    room_id: string;
    user_id: string | null;
    user: ChatUserRef | null;
    body: string;
    mentions: number[];
    deleted_at: string | null;
    created_at: string | null;
}

export interface ChatMemberDto {
    user_id: string;
    name: string | null;
    username: string | null;
    role: string | null;
    muted_until: string | null;
    /** Set by `CompetitionMemberAnnotator` when the room is a competition room. */
    is_admin?: boolean;
    /**
     * True when this user is a designated referee for the competition the
     * chat room belongs to. Surfaced as a small badge in MessageItem.
     */
    is_referee?: boolean;
    team_id?: string | null;
    team_name?: string | null;
    team_tag?: string | null;
}

export interface ChatRoomDto {
    id: string;
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
