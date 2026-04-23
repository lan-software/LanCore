<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Archive, Bell, ChevronLeft } from 'lucide-vue-next';
import {
    archivedIndex as notificationsArchive,
    index as notificationsIndex,
} from '@/actions/App/Domain/Notification/Http/Controllers/NotificationController';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
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
    { title: 'Archive', href: notificationsArchive().url },
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

    if (type === 'AchievementEarnedNotification' && data.name) {
        return `Achievement unlocked: ${data.name}`;
    }

    if (type === 'TicketTokenRotatedNotification') {
        return data.event_name
            ? `Ticket QR updated: ${data.event_name}`
            : 'Ticket QR updated';
    }

    return 'New notification';
}

function notificationDescription(notification: AppNotification): string | null {
    const type = notification.type.split('\\').pop() ?? '';
    const data = notification.data;

    if (
        type === 'UserAttributesUpdatedNotification' &&
        Array.isArray(data.changed_attributes)
    ) {
        return `Updated fields: ${(data.changed_attributes as string[]).join(', ')}`;
    }

    if (type === 'AchievementEarnedNotification' && data.description) {
        return data.description as string;
    }

    if (type === 'TicketTokenRotatedNotification') {
        const reason = data.reason as string | undefined;
        const reasonText =
            reason === 'user-added'
                ? 'A user was added to the ticket.'
                : reason === 'user-removed'
                  ? 'A user was removed from the ticket.'
                  : reason === 'manager-changed'
                    ? 'The ticket manager changed.'
                    : reason === 'user-requested'
                      ? 'You (or the ticket manager) requested a refresh.'
                      : 'The ticket QR was refreshed.';

        return `${reasonText} Previously printed copies are no longer valid.`;
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

function goToPage(page: number) {
    router.get(notificationsArchive().url, { page }, { preserveScroll: true });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Archived Notifications" />

        <div class="px-4 py-6 md:px-6">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <Heading
                    title="Archived Notifications"
                    description="Notifications you have previously archived."
                />
                <Button variant="outline" size="sm" as-child>
                    <Link :href="notificationsIndex().url">
                        <ChevronLeft class="mr-2 size-4" />
                        Back to notifications
                    </Link>
                </Button>
            </div>

            <div v-if="notifications.data.length > 0" class="mt-6 space-y-2">
                <div
                    v-for="notification in notifications.data"
                    :key="notification.id"
                    class="flex items-start gap-4 rounded-lg border bg-card p-4 shadow-sm"
                >
                    <div class="mt-0.5 rounded-full p-2 text-muted-foreground">
                        <Bell class="size-4" />
                    </div>

                    <div class="min-w-0 flex-1">
                        <p class="text-sm leading-snug text-muted-foreground">
                            {{ notificationLabel(notification) }}
                        </p>
                        <p
                            v-if="notificationDescription(notification)"
                            class="mt-0.5 text-sm text-muted-foreground"
                        >
                            {{ notificationDescription(notification) }}
                        </p>
                        <p class="mt-1 text-xs text-muted-foreground">
                            {{ formatDate(notification.created_at) }}
                            <span v-if="notification.read_at" class="ml-2"
                                >· Read</span
                            >
                        </p>
                    </div>
                </div>
            </div>

            <div
                v-else
                class="mt-16 flex flex-col items-center gap-3 text-center text-muted-foreground"
            >
                <Archive class="size-12 opacity-30" />
                <p class="text-lg font-medium">No archived notifications</p>
                <p class="text-sm">
                    Notifications you archive will appear here.
                </p>
            </div>

            <!-- Pagination -->
            <div
                v-if="notifications.last_page > 1"
                class="mt-6 flex items-center justify-between text-sm text-muted-foreground"
            >
                <p>
                    Showing {{ notifications.from }}–{{ notifications.to }} of
                    {{ notifications.total }}
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
                        :disabled="
                            notifications.current_page ===
                            notifications.last_page
                        "
                        @click="goToPage(notifications.current_page + 1)"
                    >
                        Next
                    </Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
