<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Archive, Bell, BellOff, Check, CheckCheck, Settings } from 'lucide-vue-next';
import {
    destroy as destroyNotification,
    index as notificationsIndex,
    markAllAsRead,
    markAsRead,
} from '@/actions/App/Domain/Notification/Http/Controllers/NotificationController';
import { edit as notificationSettingsEdit } from '@/routes/notifications';
import { Button } from '@/components/ui/button';
import Heading from '@/components/Heading.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { AppNotification, BreadcrumbItem } from '@/types';

type PaginatedNotifications = {
    data: AppNotification[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
};

defineProps<{
    notifications: PaginatedNotifications;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Notifications', href: notificationsIndex().url },
];

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

function notificationDescription(notification: AppNotification): string | null {
    const type = notification.type.split('\\').pop() ?? '';
    const data = notification.data;

    if (type === 'UserAttributesUpdatedNotification' && Array.isArray(data.changed_attributes)) {
        return `Updated fields: ${(data.changed_attributes as string[]).join(', ')}`;
    }

    return null;
}

function formatDate(iso: string): string {
    return new Date(iso).toLocaleString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function handleMarkAsRead(notification: AppNotification) {
    router.patch(markAsRead(notification.id).url, {}, { preserveScroll: true });
}

function handleMarkAllAsRead() {
    router.patch(markAllAsRead().url, {}, { preserveScroll: true });
}

function handleArchive(notification: AppNotification) {
    router.delete(destroyNotification(notification.id).url, { preserveScroll: true });
}

function goToPage(page: number) {
    router.get(notificationsIndex().url, { page }, { preserveScroll: true });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Notifications" />

        <div class="px-4 py-6 md:px-6">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <Heading title="Notifications" description="Your recent notifications and activity." />
                <div class="flex gap-2">
                    <Button
                        v-if="notifications.total > 0"
                        variant="outline"
                        size="sm"
                        @click="handleMarkAllAsRead"
                    >
                        <CheckCheck class="mr-2 size-4" />
                        Mark all as read
                    </Button>
                    <Button variant="outline" size="sm" as-child>
                        <Link :href="notificationSettingsEdit()">
                            <Settings class="mr-2 size-4" />
                            Notification settings
                        </Link>
                    </Button>
                </div>
            </div>

            <div v-if="notifications.data.length > 0" class="mt-6 space-y-2">
                <div
                    v-for="notification in notifications.data"
                    :key="notification.id"
                    class="group flex items-start gap-4 rounded-lg border bg-card p-4 shadow-sm transition-colors"
                    :class="{ 'border-primary/20 bg-primary/5': !notification.read_at }"
                >
                    <div class="mt-0.5 rounded-full p-2 text-muted-foreground" :class="{ 'bg-primary/10 text-primary': !notification.read_at }">
                        <Bell class="size-4" />
                    </div>

                    <div class="min-w-0 flex-1">
                        <p
                            class="text-sm leading-snug"
                            :class="{ 'font-semibold': !notification.read_at }"
                        >
                            {{ notificationLabel(notification) }}
                        </p>
                        <p v-if="notificationDescription(notification)" class="mt-0.5 text-sm text-muted-foreground">
                            {{ notificationDescription(notification) }}
                        </p>
                        <p class="mt-1 text-xs text-muted-foreground">
                            {{ formatDate(notification.created_at) }}
                            <span v-if="notification.read_at" class="ml-2">· Read</span>
                        </p>
                    </div>

                    <div class="flex shrink-0 gap-1 opacity-0 transition-opacity group-hover:opacity-100">
                        <Button
                            v-if="!notification.read_at"
                            variant="ghost"
                            size="icon"
                            class="size-8"
                            title="Mark as read"
                            @click="handleMarkAsRead(notification)"
                        >
                            <Check class="size-4" />
                        </Button>
                        <Button
                            variant="ghost"
                            size="icon"
                            class="size-8 hover:text-destructive"
                            title="Archive"
                            @click="handleArchive(notification)"
                        >
                            <Archive class="size-4" />
                        </Button>
                    </div>
                </div>
            </div>

            <div v-else class="mt-16 flex flex-col items-center gap-3 text-center text-muted-foreground">
                <BellOff class="size-12 opacity-30" />
                <p class="text-lg font-medium">No notifications</p>
                <p class="text-sm">You're all caught up! Notifications will appear here.</p>
            </div>

            <!-- Pagination -->
            <div
                v-if="notifications.last_page > 1"
                class="mt-6 flex items-center justify-between text-sm text-muted-foreground"
            >
                <p>
                    Showing {{ notifications.from }}–{{ notifications.to }} of {{ notifications.total }}
                </p>
                <div class="flex gap-2">
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="notifications.current_page === 1"
                        @click="goToPage(notifications.current_page - 1)"
                    >
                        Previous
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="notifications.current_page === notifications.last_page"
                        @click="goToPage(notifications.current_page + 1)"
                    >
                        Next
                    </Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
