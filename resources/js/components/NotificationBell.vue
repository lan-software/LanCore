<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { useEcho } from '@laravel/echo-vue';
import {
    Archive,
    Bell,
    BellOff,
    Check,
    CheckCheck,
    ExternalLink,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import {
    archive as archiveNotification,
    index as notificationsIndex,
    markAllAsRead,
    markAsRead,
} from '@/actions/App/Domain/Notification/Http/Controllers/NotificationController';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import type { AppNotification } from '@/types';

const { t } = useI18n();
const page = usePage();

// Server-rendered snapshot, kept in a ref so Echo events can push new
// notifications into the list without a page reload. Re-syncs whenever
// Inertia replaces the page props (e.g. after navigation or partial reload).
const liveUnread = ref<number>((page.props.unreadNotificationsCount as number) ?? 0);
const liveNotifications = ref<AppNotification[]>(
    (page.props.recentNotifications as AppNotification[]) ?? [],
);

watch(
    () => page.props.unreadNotificationsCount,
    (next) => {
        liveUnread.value = (next as number) ?? 0;
    },
);

watch(
    () => page.props.recentNotifications,
    (next) => {
        liveNotifications.value = (next as AppNotification[]) ?? [];
    },
);

const initialUserId =
    (page.props.auth as { user?: { id?: string } } | undefined)?.user?.id ?? '';

interface NotificationReceivedPayload {
    id: string;
    type: string;
    data: AppNotification['data'];
    created_at: string | null;
    read_at: string | null;
}

// Subscribe unconditionally — when `initialUserId` is 0 the channel auth
// rejects and Echo no-ops. Calling useEcho inside a conditional has been
// flaky in practice (the composable's internal scheduling assumes a stable
// setup-time call site), so we always call it.
 
console.info('[NotificationBell] subscribing for user', initialUserId);
useEcho<NotificationReceivedPayload>(
    `App.Models.User.${initialUserId}`,
    '.notification.received',
    (payload) => {
         
        console.info('[NotificationBell] received', payload);

        if (
            typeof payload.id !== 'string' ||
            typeof payload.type !== 'string'
        ) {
            return;
        }

        if (liveNotifications.value.some((n) => n.id === payload.id)) {
            return;
        }

        liveNotifications.value = [
            {
                id: payload.id,
                type: payload.type,
                data: payload.data ?? ({} as AppNotification['data']),
                read_at: payload.read_at,
                created_at: payload.created_at ?? new Date().toISOString(),
            },
            ...liveNotifications.value,
        ].slice(0, 5);
        liveUnread.value += 1;
    },
    [initialUserId],
);

const unreadCount = computed(() => liveUnread.value);
const recentNotifications = computed<AppNotification[]>(
    () => liveNotifications.value,
);

function notificationUrl(notification: AppNotification): string {
    const type = notification.type.split('\\').pop() ?? '';
    const data = notification.data;

    if (
        type === 'TicketTokenRotatedNotification' &&
        typeof data.ticket_id === 'number'
    ) {
        return `/portal/tickets/${data.ticket_id}`;
    }

    if (
        type === 'TicketSaleNotification' &&
        typeof data.shop_url === 'string'
    ) {
        return data.shop_url;
    }

    if (type === 'ChatMentionNotification') {
        if (typeof data.target_url === 'string' && data.target_url !== '') {
            return data.target_url;
        }

        if (typeof data.room_id === 'number') {
            return `/chat/rooms/${data.room_id}`;
        }
    }

    return notificationsIndex().url;
}

function handleNotificationClick(notification: AppNotification): void {
    const target = notificationUrl(notification);

    if (!notification.read_at) {
        router.patch(
            markAsRead(notification.id).url,
            {},
            {
                preserveScroll: true,
                onFinish: () => router.visit(target),
            },
        );

        return;
    }

    router.visit(target);
}

function notificationLabel(notification: AppNotification): string {
    const type = notification.type.split('\\').pop() ?? '';
    const data = notification.data;

    if (type === 'NewsPublishedNotification' && data.title) {
        return t('notifications.types.newArticle', { title: data.title });
    }

    if (type === 'AnnouncementPublishedNotification' && data.title) {
        return t('notifications.types.announcement', { title: data.title });
    }

    if (type === 'ProgramTimeSlotNotification') {
        return t('notifications.types.upcomingProgramSlot');
    }

    if (type === 'UserAttributesUpdatedNotification') {
        return t('notifications.types.profileUpdated');
    }

    if (type === 'UserRolesChangedNotification') {
        return t('notifications.types.rolesChanged');
    }

    if (type === 'AchievementEarnedNotification' && data.name) {
        return t('notifications.types.achievementUnlocked', {
            name: data.name,
        });
    }

    if (type === 'TicketTokenRotatedNotification') {
        return data.event_name
            ? t('notifications.types.ticketQrUpdatedNamed', {
                  eventName: data.event_name,
              })
            : t('notifications.types.ticketQrUpdated');
    }

    if (type === 'TicketSaleNotification' && data.ticket_type_name) {
        const key =
            data.phase === 'end'
                ? 'notifications.types.ticketSaleEnd'
                : 'notifications.types.ticketSaleRelease';

        return t(key, { ticketName: data.ticket_type_name });
    }

    if (type === 'ChatMentionNotification') {
        const who = data.author_username
            ? `@${data.author_username}`
            : (data.author_name ?? t('notifications.types.chatMentionFallbackAuthor'));

        return t('notifications.types.chatMention', { who });
    }

    return t('notifications.types.generic');
}

function handleMarkAsRead(notification: AppNotification) {
    const target = liveNotifications.value.find((n) => n.id === notification.id);

    if (target && !target.read_at) {
        target.read_at = new Date().toISOString();
        liveUnread.value = Math.max(0, liveUnread.value - 1);
    }

    router.patch(markAsRead(notification.id).url, {}, { preserveScroll: true });
}

function handleMarkAllAsRead() {
    const now = new Date().toISOString();
    liveNotifications.value = liveNotifications.value.map((n) =>
        n.read_at ? n : { ...n, read_at: now },
    );
    liveUnread.value = 0;
    router.patch(markAllAsRead().url, {}, { preserveScroll: true });
}

function handleArchive(notification: AppNotification) {
    const wasUnread = !liveNotifications.value.find(
        (n) => n.id === notification.id,
    )?.read_at;
    liveNotifications.value = liveNotifications.value.filter(
        (n) => n.id !== notification.id,
    );

    if (wasUnread) {
        liveUnread.value = Math.max(0, liveUnread.value - 1);
    }

    router.patch(
        archiveNotification(notification.id).url,
        {},
        { preserveScroll: true },
    );
}
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button
                variant="ghost"
                size="icon"
                class="relative"
                :aria-label="$t('notifications.heading')"
            >
                <Bell class="size-5" />
                <span
                    v-if="unreadCount > 0"
                    class="absolute -top-0.5 -right-0.5 flex size-4 items-center justify-center rounded-full bg-destructive text-[10px] font-bold text-destructive-foreground"
                >
                    {{ unreadCount > 99 ? '99+' : unreadCount }}
                </span>
            </Button>
        </DropdownMenuTrigger>

        <DropdownMenuContent class="w-80" align="end" :side-offset="8">
            <DropdownMenuLabel class="flex items-center justify-between">
                <span>{{ $t('notifications.heading') }}</span>
                <Button
                    v-if="unreadCount > 0"
                    variant="ghost"
                    size="sm"
                    class="h-auto px-2 py-1 text-xs"
                    @click="handleMarkAllAsRead"
                >
                    <CheckCheck class="mr-1 size-3" />
                    {{ $t('notifications.markAllRead') }}
                </Button>
            </DropdownMenuLabel>

            <DropdownMenuSeparator />

            <DropdownMenuGroup
                v-if="recentNotifications.length > 0"
                class="max-h-72 overflow-y-auto"
            >
                <DropdownMenuItem
                    v-for="notification in recentNotifications"
                    :key="notification.id"
                    class="group flex cursor-pointer flex-col items-start gap-1 px-3 py-2.5"
                    @select="handleNotificationClick(notification)"
                >
                    <div class="flex w-full items-start justify-between gap-2">
                        <div class="flex items-start gap-2">
                            <span
                                v-if="!notification.read_at"
                                class="mt-1.5 size-2 shrink-0 rounded-full bg-primary"
                            />
                            <span v-else class="mt-1.5 size-2 shrink-0" />
                            <p
                                class="text-sm leading-tight"
                                :class="{
                                    'font-medium': !notification.read_at,
                                }"
                            >
                                {{ notificationLabel(notification) }}
                            </p>
                        </div>
                        <div
                            class="flex shrink-0 gap-1 opacity-0 transition-opacity group-hover:opacity-100"
                        >
                            <button
                                v-if="!notification.read_at"
                                class="rounded p-0.5 hover:bg-muted"
                                :title="$t('notifications.markAsRead')"
                                @click.stop="handleMarkAsRead(notification)"
                            >
                                <Check class="size-3" />
                            </button>
                            <button
                                class="rounded p-0.5 hover:bg-muted"
                                :title="$t('notifications.archive')"
                                @click.stop="handleArchive(notification)"
                            >
                                <Archive class="size-3" />
                            </button>
                        </div>
                    </div>
                    <p class="ml-4 text-xs text-muted-foreground">
                        {{ new Date(notification.created_at).toLocaleString() }}
                    </p>
                </DropdownMenuItem>
            </DropdownMenuGroup>

            <div
                v-else
                class="flex flex-col items-center gap-2 px-4 py-6 text-sm text-muted-foreground"
            >
                <BellOff class="size-8 opacity-40" />
                <p>{{ $t('notifications.empty') }}</p>
            </div>

            <DropdownMenuSeparator />

            <div class="p-2">
                <Button variant="outline" class="w-full" as-child>
                    <Link :href="notificationsIndex().url">
                        <ExternalLink class="mr-2 size-4" />
                        {{ $t('notifications.viewAll') }}
                    </Link>
                </Button>
            </div>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
