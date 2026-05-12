// @see docs/mil-std-498/SRS.md PRS-F-005, PRS-F-007
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

export type PresenceStatus = 'active' | 'idle' | 'offline';

type SharedPresenceProp = { status: PresenceStatus } | null;

export function useMyPresence() {
    const page = usePage();

    return computed<PresenceStatus>(
        () => (page.props.presence as SharedPresenceProp)?.status ?? 'offline',
    );
}

/**
 * Page-scoped presence map for a list of user IDs. v1 reads a server-rendered
 * `presence: Record<number, PresenceStatus>` prop. A real-time variant will
 * arrive with the Chat CSCI when the broadcast channel exists.
 */
export function useUsersPresence(userIds: number[]) {
    const page = usePage();

    return computed<Record<number, PresenceStatus>>(() => {
        const map =
            (page.props.presence as
                | Record<number, PresenceStatus>
                | undefined) ?? {};
        const result: Record<number, PresenceStatus> = {};

        for (const id of userIds) {
            result[id] = map[id] ?? 'offline';
        }

        return result;
    });
}
