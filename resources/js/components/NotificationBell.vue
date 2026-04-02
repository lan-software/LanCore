<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { Archive, Bell, BellOff, Check, CheckCheck, ExternalLink } from 'lucide-vue-next';
import { computed } from 'vue';
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

const page = usePage();

const unreadCount = computed(() => page.props.unreadNotificationsCount ?? 0);
const recentNotifications = computed<AppNotification[]>(() => page.props.recentNotifications ?? []);

function notificationLabel(notification: AppNotification): string {
    const type = notification.type.split('\\').pop() ?? '';
    const data = notification.data;

    if (type === 'NewsPublishedNotification' && data.title) {
        return `New article: ${data.title}`;
    }

    if (type === 'AnnouncementPublishedNotification' && data.title) {
        return `Announcement: ${data.title}`;
    }

    if (type === 'ProgramTimeSlotNotification') {
        return 'Upcoming program time slot';
    }

    if (type === 'UserAttributesUpdatedNotification') {
        return 'Your profile was updated';
    }

    if (type === 'UserRolesChangedNotification') {
        return 'Your roles have changed';
    }

    return 'New notification';
}

function handleMarkAsRead(notification: AppNotification) {
    router.patch(markAsRead(notification.id).url, {}, { preserveScroll: true });
}

function handleMarkAllAsRead() {
    router.patch(markAllAsRead().url, {}, { preserveScroll: true });
}

function handleArchive(notification: AppNotification) {
    router.patch(archiveNotification(notification.id).url, {}, { preserveScroll: true });
}
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button variant="ghost" size="icon" class="relative" aria-label="Notifications">
                <Bell class="size-5" />
                <span
                    v-if="unreadCount > 0"
                    class="absolute -right-0.5 -top-0.5 flex size-4 items-center justify-center rounded-full bg-destructive text-[10px] font-bold text-destructive-foreground"
                >
                    {{ unreadCount > 99 ? '99+' : unreadCount }}
                </span>
            </Button>
        </DropdownMenuTrigger>

        <DropdownMenuContent class="w-80" align="end" :side-offset="8">
            <DropdownMenuLabel class="flex items-center justify-between">
                <span>Notifications</span>
                <Button
                    v-if="unreadCount > 0"
                    variant="ghost"
                    size="sm"
                    class="h-auto px-2 py-1 text-xs"
                    @click="handleMarkAllAsRead"
                >
                    <CheckCheck class="mr-1 size-3" />
                    Mark all read
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
                    class="group flex cursor-default flex-col items-start gap-1 px-3 py-2.5"
                    @select.prevent
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
                                    :class="{ 'font-medium': !notification.read_at }"
                                >
                                    {{ notificationLabel(notification) }}
                                </p>
                            </div>
                            <div class="flex shrink-0 gap-1 opacity-0 transition-opacity group-hover:opacity-100">
                                <button
                                    v-if="!notification.read_at"
                                    class="rounded p-0.5 hover:bg-muted"
                                    :title="'Mark as read'"
                                    @click.stop="handleMarkAsRead(notification)"
                                >
                                    <Check class="size-3" />
                                </button>
                                <button
                                    class="rounded p-0.5 hover:bg-muted"
                                    title="Archive"
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

            <div v-else class="flex flex-col items-center gap-2 px-4 py-6 text-sm text-muted-foreground">
                <BellOff class="size-8 opacity-40" />
                <p>No notifications yet</p>
            </div>

            <DropdownMenuSeparator />

            <div class="p-2">
                <Button variant="outline" class="w-full" as-child>
                    <Link :href="notificationsIndex().url">
                        <ExternalLink class="mr-2 size-4" />
                        View all notifications
                    </Link>
                </Button>
            </div>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
